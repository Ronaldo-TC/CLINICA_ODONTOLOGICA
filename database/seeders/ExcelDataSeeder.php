<?php

namespace Database\Seeders;

use App\Models\Usuario;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class ExcelDataSeeder extends Seeder
{
    /** @var array<string, string> */
    private const ROLE_BY_LEGACY_TYPE = [
        'admin' => 'admin',
        'admission' => 'admision',
        'box' => 'cajas',
        'sterilization' => 'esterilizacion',
        'student' => 'estudiante',
        'supplies' => 'insumos',
        'teacher' => 'docente',
    ];

    public function run(): void
    {
        $clinicas = $this->readCsv('clinica.csv');
        $usuarios = $this->prepareUsuarios($this->readCsv('usuarios.csv', ';'));
        $especialidades = $this->readCsv('especialidades.csv');
        $tiposInsumo = $this->readCsv('insumo-tratamiento.csv');
        $insumos = $this->readCsv('insumos.csv');
        $tratamientos = $this->readCsv('tratamientos.csv');

        $clinicaIds = $this->importClinicas($clinicas);
        [$usuarioIds, $usuarioTipos] = $this->importUsuarios($usuarios);

        $this->syncRoles($usuarioIds, $usuarioTipos);
        $this->importEspecialidades($especialidades, $usuarioIds, $clinicaIds);

        $tipoInsumoIds = $this->importTiposInsumo($tiposInsumo, $clinicaIds);

        $this->importInsumos($insumos, $clinicaIds, $tipoInsumoIds);
        $this->importTratamientos($tratamientos, $clinicaIds);

        $this->command?->info(sprintf(
            'CSV importado: %d clinicas, %d usuarios, %d asignaciones, %d tipos de insumo, %d insumos y %d tratamientos.',
            count($clinicas),
            count($usuarios),
            count($especialidades),
            count($tiposInsumo),
            count($insumos),
            count($tratamientos),
        ));
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return array<int, int>
     */
    private function importClinicas(array $rows): array
    {
        $ids = [];

        foreach ($rows as $row) {
            $legacyId = $this->integer($row, 'id');
            $values = [
                'modulo' => trim($row['modulo']),
                'especialidad' => trim($row['especialidad']),
                'curso' => $this->integer($row, 'curso'),
                'updated_at' => $this->dateTime($row['updated_at']) ?? now(),
            ];

            $existingId = DB::table('clinicas')
                ->where('modulo', $values['modulo'])
                ->where('especialidad', $values['especialidad'])
                ->value('id');

            if ($existingId !== null) {
                DB::table('clinicas')->where('id', $existingId)->update($values);
                $ids[$legacyId] = (int) $existingId;

                continue;
            }

            $ids[$legacyId] = (int) DB::table('clinicas')->insertGetId($values + [
                'created_at' => $values['updated_at'],
            ]);
        }

        return $ids;
    }

    /**
     * @param  list<array{legacy_id: int, ci: string, nombre_usuario: string, nombre_completo: string, descripcion: string, tipo: string, habilitado: bool, permitir_login: bool, multilogin: bool, contrasena: string, ip: ?string, ultimo_login: ?string, ultimo_logout: ?string, ip_permitida: ?string, info: string, updated_at: string}>  $rows
     * @return array{array<int, int>, array<int, string>}
     */
    private function importUsuarios(array $rows): array
    {
        $ids = [];
        $types = [];

        foreach ($rows as $row) {
            $existing = DB::table('usuarios')
                ->where('nombre_usuario', $row['nombre_usuario'])
                ->first(['id', 'contrasena']);

            $existingId = $existing === null ? null : (int) $existing->id;
            $ci = $this->availableCi($row['ci'], $row['legacy_id'], $existingId);
            $passwordHash = $this->passwordHash($row['contrasena'], $existing?->contrasena);

            $values = [
                'ci' => $ci,
                'nombre_usuario' => $row['nombre_usuario'],
                'nombre_completo' => $row['nombre_completo'],
                'descripcion' => $row['descripcion'],
                'tipo' => $row['tipo'],
                'habilitado' => $row['habilitado'],
                'permitir_login' => $row['permitir_login'],
                'multilogin' => $row['multilogin'],
                'contrasena' => $passwordHash,
                'ip' => $row['ip'],
                'ultimo_login' => $row['ultimo_login'],
                'ultimo_logout' => $row['ultimo_logout'],
                'ip_permitida' => $row['ip_permitida'],
                'info' => $row['info'],
                'updated_at' => $row['updated_at'],
            ];

            if ($existingId !== null) {
                DB::table('usuarios')->where('id', $existingId)->update($values);
                $id = $existingId;
            } else {
                $id = (int) DB::table('usuarios')->insertGetId($values + [
                    'sesion' => '',
                    'sesion_extra' => '',
                    'created_at' => $row['updated_at'],
                ]);
            }

            $ids[$row['legacy_id']] = $id;
            $types[$row['legacy_id']] = $row['tipo'];
        }

        return [$ids, $types];
    }

    /**
     * @param  array<int, int>  $usuarioIds
     * @param  array<int, string>  $usuarioTipos
     */
    private function syncRoles(array $usuarioIds, array $usuarioTipos): void
    {
        $roles = DB::table('roles')
            ->where('guard_name', 'web')
            ->pluck('id', 'name');
        $modelType = (new Usuario)->getMorphClass();
        $importedIds = array_values($usuarioIds);

        foreach (array_chunk($importedIds, 500) as $ids) {
            DB::table('model_has_roles')
                ->where('model_type', $modelType)
                ->whereIn('model_id', $ids)
                ->delete();
        }

        $assignments = [];

        foreach ($usuarioIds as $legacyId => $usuarioId) {
            $roleName = self::ROLE_BY_LEGACY_TYPE[mb_strtolower($usuarioTipos[$legacyId])] ?? null;

            if ($roleName === null) {
                continue;
            }

            $roleId = $roles->get($roleName);

            if ($roleId === null) {
                throw new RuntimeException("No existe el rol requerido [{$roleName}]. Ejecute primero RolesSeeder.");
            }

            $assignments[] = [
                'role_id' => $roleId,
                'model_type' => $modelType,
                'model_id' => $usuarioId,
            ];
        }

        foreach (array_chunk($assignments, 500) as $chunk) {
            DB::table('model_has_roles')->insertOrIgnore($chunk);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  array<int, int>  $usuarioIds
     * @param  array<int, int>  $clinicaIds
     */
    private function importEspecialidades(array $rows, array $usuarioIds, array $clinicaIds): void
    {
        $values = [];

        foreach ($rows as $row) {
            $legacyUsuarioId = $this->integer($row, 'usuario_id');
            $legacyClinicaId = $this->integer($row, 'clinica_id');
            $updatedAt = $this->dateTime($row['updated_at']) ?? now();

            $values[] = [
                'usuario_id' => $this->mappedId($usuarioIds, $legacyUsuarioId, 'usuario'),
                'clinica_id' => $this->mappedId($clinicaIds, $legacyClinicaId, 'clinica'),
                'habilitado' => $this->boolean($row, 'habilitado'),
                'rotacion' => $this->integer($row, 'rotacion'),
                'responsable_admision' => $this->boolean($row, 'responsable_admision'),
                'fecha_inicio' => $this->date($row['fecha_inicio']),
                'created_at' => $updatedAt,
                'updated_at' => $updatedAt,
            ];
        }

        foreach (array_chunk($values, 500) as $chunk) {
            DB::table('especialidades')->upsert(
                $chunk,
                ['usuario_id', 'clinica_id'],
                ['habilitado', 'rotacion', 'responsable_admision', 'fecha_inicio', 'updated_at'],
            );
        }
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  array<int, int>  $clinicaIds
     * @return array<int, int>
     */
    private function importTiposInsumo(array $rows, array $clinicaIds): array
    {
        $ids = [];

        foreach ($rows as $row) {
            $legacyId = $this->integer($row, 'id');
            $clinicaId = $this->mappedId($clinicaIds, $this->integer($row, 'clinica_id'), 'clinica');
            $values = [
                'nombre' => trim($row['nombre']),
                'descripcion' => trim($row['descripcion']),
                'clinica_id' => $clinicaId,
                'updated_at' => $this->dateTime($row['updated_at']) ?? now(),
            ];
            $existingId = DB::table('insumos_tratamiento')
                ->where('clinica_id', $clinicaId)
                ->where('nombre', $values['nombre'])
                ->value('id');

            if ($existingId !== null) {
                DB::table('insumos_tratamiento')->where('id', $existingId)->update($values);
                $ids[$legacyId] = (int) $existingId;

                continue;
            }

            $ids[$legacyId] = (int) DB::table('insumos_tratamiento')->insertGetId($values + [
                'created_at' => $values['updated_at'],
            ]);
        }

        return $ids;
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  array<int, int>  $clinicaIds
     * @param  array<int, int>  $tipoInsumoIds
     */
    private function importInsumos(array $rows, array $clinicaIds, array $tipoInsumoIds): void
    {
        foreach ($rows as $row) {
            $legacyId = $this->integer($row, 'id');
            $clinicaId = $this->mappedId($clinicaIds, $this->integer($row, 'clinica_id'), 'clinica');
            $values = [
                'nombre' => trim($row['nombre']),
                'insumo_tratamiento_id' => $this->mappedId($tipoInsumoIds, $legacyId, 'tipo de insumo'),
                'clinica_id' => $clinicaId,
                'updated_at' => $this->dateTime($row['updated_at']) ?? now(),
            ];
            $existingId = DB::table('insumos')
                ->where('clinica_id', $clinicaId)
                ->where('nombre', $values['nombre'])
                ->value('id');

            if ($existingId !== null) {
                DB::table('insumos')->where('id', $existingId)->update($values);

                continue;
            }

            DB::table('insumos')->insert($values + [
                'cantidad_numerica' => 0,
                'cantidad' => '',
                'stock_minimo' => 0,
                'costo_unitario' => 0,
                'created_at' => $values['updated_at'],
            ]);
        }
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  array<int, int>  $clinicaIds
     */
    private function importTratamientos(array $rows, array $clinicaIds): void
    {
        foreach ($rows as $row) {
            $clinicaId = $this->mappedId($clinicaIds, $this->integer($row, 'clinica_id'), 'clinica');
            $values = [
                'nombre' => trim($row['nombre']),
                'costo' => (float) $row['costo'],
                'descripcion' => trim($row['descripcion']),
                'precio_asignado' => true,
                'clinica_id' => $clinicaId,
                'updated_at' => $this->dateTime($row['updated_at']) ?? now(),
            ];
            $existingId = DB::table('tratamientos')
                ->where('clinica_id', $clinicaId)
                ->where('nombre', $values['nombre'])
                ->value('id');

            if ($existingId !== null) {
                DB::table('tratamientos')->where('id', $existingId)->update($values);

                continue;
            }

            DB::table('tratamientos')->insert($values + [
                'created_at' => $values['updated_at'],
            ]);
        }
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return list<array{legacy_id: int, ci: string, nombre_usuario: string, nombre_completo: string, descripcion: string, tipo: string, habilitado: bool, permitir_login: bool, multilogin: bool, contrasena: string, ip: ?string, ultimo_login: ?string, ultimo_logout: ?string, ip_permitida: ?string, info: string, updated_at: string}>
     */
    private function prepareUsuarios(array $rows): array
    {
        usort($rows, fn (array $left, array $right): int => $this->integer($left, 'id') <=> $this->integer($right, 'id'));

        $seenUsernames = [];
        $seenCis = [];
        $prepared = [];

        foreach ($rows as $row) {
            $legacyId = $this->integer($row, 'id');
            $rawUsername = $this->nullable($row['nombre_usuario']) ?? "legacy_{$legacyId}";
            $username = $this->uniqueValue($rawUsername, $legacyId, $seenUsernames, 20, '_');
            $rawCi = $this->nullable($row['ci']);

            if ($rawCi === null || $rawCi === '0') {
                $rawCi = "LEGACY-{$legacyId}";
            }

            $ci = $this->uniqueValue($rawCi, $legacyId, $seenCis, 50, '-');
            $updatedAt = $this->dateTime($row['updated_at']) ?? now()->format('Y-m-d H:i:s');

            $prepared[] = [
                'legacy_id' => $legacyId,
                'ci' => $ci,
                'nombre_usuario' => $username,
                'nombre_completo' => $this->nullable($row['nombre_completo']) ?? "Usuario importado {$legacyId}",
                'descripcion' => $this->nullable($row['descripcion']) ?? '',
                'tipo' => $this->nullable($row['tipo']) ?? '',
                'habilitado' => $this->boolean($row, 'habilitado'),
                'permitir_login' => $this->boolean($row, 'permitir_login'),
                'multilogin' => $this->boolean($row, 'multilogin'),
                'contrasena' => $row['contrasena'],
                'ip' => $this->nullable($row['ip']),
                'ultimo_login' => $this->dateTime($row['ultimo_login']),
                'ultimo_logout' => $this->dateTime($row['ultimo_logout']),
                'ip_permitida' => $this->nullable($row['ip_permitida']),
                'info' => $this->nullable($row['info']) ?? '',
                'updated_at' => $updatedAt,
            ];
        }

        return $prepared;
    }

    /** @param array<string, true> $seen */
    private function uniqueValue(string $value, int $legacyId, array &$seen, int $maximumLength, string $separator): string
    {
        $value = mb_substr(trim($value), 0, $maximumLength);
        $key = mb_strtolower($value);

        if (! isset($seen[$key])) {
            $seen[$key] = true;

            return $value;
        }

        $suffix = $separator.$legacyId;
        $base = mb_substr($value, 0, $maximumLength - mb_strlen($suffix));
        $candidate = $base.$suffix;
        $key = mb_strtolower($candidate);
        $attempt = 2;

        while (isset($seen[$key])) {
            $numberedSuffix = $suffix.$separator.$attempt;
            $candidate = mb_substr($value, 0, $maximumLength - mb_strlen($numberedSuffix)).$numberedSuffix;
            $key = mb_strtolower($candidate);
            $attempt++;
        }

        $seen[$key] = true;

        return $candidate;
    }

    private function availableCi(string $ci, int $legacyId, ?int $existingId): string
    {
        $candidate = $ci;
        $attempt = 1;

        while (DB::table('usuarios')
            ->where('ci', $candidate)
            ->when($existingId !== null, fn ($query) => $query->where('id', '!=', $existingId))
            ->exists()) {
            $suffix = '-'.$legacyId.($attempt === 1 ? '' : '-'.$attempt);
            $candidate = mb_substr($ci, 0, 50 - mb_strlen($suffix)).$suffix;
            $attempt++;
        }

        return $candidate;
    }

    private function passwordHash(string $plainPassword, ?string $existingHash): string
    {
        if ($existingHash !== null && $existingHash !== '') {
            try {
                if (Hash::check($plainPassword, $existingHash)) {
                    return $existingHash;
                }
            } catch (Throwable) {
                // La base anterior podia contener contrasenas sin hash.
            }
        }

        return Hash::make($plainPassword);
    }

    /** @return list<array<string, string>> */
    private function readCsv(string $filename, string $delimiter = ','): array
    {
        $path = database_path('excel'.DIRECTORY_SEPARATOR.$filename);

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("No se puede leer el archivo [{$path}].");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("No se pudo abrir el archivo [{$path}].");
        }

        $headers = null;
        $rows = [];
        $wrappedRows = false;
        $lineNumber = 0;

        try {
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                $line = rtrim($line, "\r\n");

                if ($line === '') {
                    continue;
                }

                if (! mb_check_encoding($line, 'UTF-8')) {
                    throw new RuntimeException("El archivo [{$filename}] no esta codificado en UTF-8 (linea {$lineNumber}).");
                }

                $fields = str_getcsv($line, $delimiter, '"', '\\');

                if ($headers === null) {
                    $wrappedRows = count($fields) === 1 && str_contains($fields[0], $delimiter);

                    if ($wrappedRows) {
                        $fields = $this->parseWrappedRow($line, $delimiter);
                    }

                    $fields[0] = ltrim($fields[0], "\xEF\xBB\xBF");
                    $headers = array_map(fn (string $header): string => trim($header), $fields);

                    continue;
                }

                if ($wrappedRows) {
                    $fields = $this->parseWrappedRow($line, $delimiter);
                }

                if (count($fields) !== count($headers)) {
                    throw new RuntimeException("Cantidad de columnas invalida en [{$filename}], linea {$lineNumber}.");
                }

                /** @var array<string, string> $row */
                $row = array_combine($headers, $fields);
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        if ($headers === null) {
            throw new RuntimeException("El archivo [{$filename}] esta vacio.");
        }

        return $rows;
    }

    /** @return list<string> */
    private function parseWrappedRow(string $line, string $delimiter): array
    {
        if (! str_starts_with($line, '"') || ! str_ends_with($line, '"')) {
            throw new RuntimeException('Se encontro una fila CSV envuelta con formato invalido.');
        }

        $normalized = str_replace('""', '"', substr($line, 1, -1));

        return str_getcsv($normalized, $delimiter, '"', '\\');
    }

    /** @param array<string, string> $row */
    private function integer(array $row, string $column): int
    {
        if (! array_key_exists($column, $row) || filter_var($row[$column], FILTER_VALIDATE_INT) === false) {
            throw new RuntimeException("El valor [{$column}] debe ser un numero entero.");
        }

        return (int) $row[$column];
    }

    /** @param array<string, string> $row */
    private function boolean(array $row, string $column): bool
    {
        $value = $this->integer($row, $column);

        if (! in_array($value, [0, 1], true)) {
            throw new RuntimeException("El valor [{$column}] debe ser 0 o 1.");
        }

        return $value === 1;
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === '' || strcasecmp($value, 'NULL') === 0 ? null : $value;
    }

    private function date(string $value): ?string
    {
        $value = $this->nullable($value);

        if ($value === null) {
            return null;
        }

        return $this->formattedDate($value, ['Y-m-d'], 'Y-m-d');
    }

    private function dateTime(string $value): ?string
    {
        $value = $this->nullable($value);

        if ($value === null) {
            return null;
        }

        return $this->formattedDate($value, ['Y-m-d H:i:s', 'd/m/Y H:i'], 'Y-m-d H:i:s');
    }

    /** @param list<string> $formats */
    private function formattedDate(string $value, array $formats, string $outputFormat): string
    {
        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format($outputFormat);
            }
        }

        throw new RuntimeException("La fecha [{$value}] tiene un formato invalido.");
    }

    /** @param array<int, int> $ids */
    private function mappedId(array $ids, int $legacyId, string $entity): int
    {
        if (! isset($ids[$legacyId])) {
            throw new RuntimeException("No existe el mapeo de {$entity} para el ID anterior [{$legacyId}].");
        }

        return $ids[$legacyId];
    }
}

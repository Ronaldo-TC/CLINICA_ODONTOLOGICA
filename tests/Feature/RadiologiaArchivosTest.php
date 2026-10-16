<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Radiologia;
use App\Models\TratamientoRealizado;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\CreatesClinicalCase;
use Tests\TestCase;

class RadiologiaArchivosTest extends TestCase
{
    use CreatesClinicalCase, DatabaseTransactions;

    public function test_dicom_upload_is_private_and_inaccessible_to_another_student(): void
    {
        Storage::fake('local');
        $case = $this->clinicalCase();
        $user = $this->clinicalUser('radiologia');
        $cash = $this->paidRadiologyReceipt($case, $user);
        $study = Radiologia::create(['descripcion' => 'Tomografía', 'observacion' => '', 'estado' => 'realizado', 'paciente_id' => $case['admission']->paciente_id, 'usuario_id' => $user->id, 'caja_id' => $cash->id, 'clinica_id' => $case['clinic']->id]);
        $file = UploadedFile::fake()->createWithContent('estudio.dcm', str_repeat(chr(0), 128).'DICM'.'test-content');

        $this->actingAs($user)->post(route('radiologia.archivos.store', $study->id), ['archivos' => [$file]])->assertRedirect();

        $stored = $study->archivos()->firstOrFail();
        Storage::disk('local')->assertExists($stored->ruta);
        $this->assertSame('dcm', $stored->extension);
        $this->actingAs($case['student'])->get(route('radiologia.archivos.ver', [$study->id, $stored->id]))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('radiologia.visor', $study->id))->assertOk()
            ->assertSee('id="dicom-pantalla"', false)
            ->assertSee('data-tool="Angle"', false)
            ->assertSee('data-tool="RectangleROI"', false)
            ->assertSee('id="dicom-reproducir"', false)
            ->assertSee('id="radiografia-viewport"', false);
        $this->actingAs($this->clinicalUser('estudiante'))->get(route('radiologia.archivos.ver', [$study->id, $stored->id]))->assertNotFound();
        $this->get(route('radiologia.visor', $study->id))->assertNotFound();
        $this->actingAs($user)->postJson(route('radiologia.archivos.store', $study->id), ['archivos' => [UploadedFile::fake()->createWithContent('falso.dcm', '<script>bad</script>')]])->assertUnprocessable();
        $this->assertSame(1, $study->archivos()->count());
    }

    public function test_guest_cannot_access_a_dicom_viewer(): void
    {
        $this->get(route('radiologia.visor', 1))->assertRedirect(route('auth.login'));
    }

    public function test_study_creation_uses_receipt_patient_and_authenticated_operator(): void
    {
        $case = $this->clinicalCase();
        $user = $this->clinicalUser('radiologia');
        $cash = $this->paidRadiologyReceipt($case, $user);
        $payload = ['descripcion' => 'Radiografía periapical', 'estado' => 'pendiente', 'caja_id' => $cash->id, 'clinica_id' => $case['clinic']->id];

        $response = $this->actingAs($user)->postJson(route('radiologia.store'), $payload)->assertCreated();

        $study = Radiologia::findOrFail($response->json('id'));
        $this->assertSame($case['admission']->paciente_id, $study->paciente_id);
        $this->assertSame($case['history']->periodo_id, $study->periodo_id);
        $this->assertSame($user->id, $study->usuario_id);
        $this->postJson(route('radiologia.store'), [...$payload, 'paciente_id' => $this->clinicalCase()['admission']->paciente_id])->assertUnprocessable()->assertJsonValidationErrors('paciente_id');
    }

    /** @param array<string, mixed> $case */
    private function paidRadiologyReceipt(array $case, Usuario $user): Caja
    {
        $treatment = TratamientoRealizado::create([
            'historia_clinica_id' => $case['history']->id, 'tratamiento_id' => $case['treatment']->id,
            'clinica_id' => $case['clinic']->id, 'estado' => 'autorizado', 'costo_cobrado' => 100,
            'autorizado_por' => $case['teacher']->id, 'fecha_autorizacion' => now(),
        ]);

        return Caja::create([
            'tratamiento_realizado_id' => $treatment->id, 'paciente_id' => $case['admission']->paciente_id,
            'periodo_id' => $case['history']->periodo_id, 'recibo' => 500000000 + $treatment->id,
            'monto' => 100, 'costo_total' => 100, 'cancelado' => 100, 'habilitada' => true,
            'tipo' => 'radiologia', 'ci' => 0, 'tratamiento_id' => $case['treatment']->id,
            'clinica_id' => $case['clinic']->id, 'usuario_id' => $user->id,
        ]);
    }
}

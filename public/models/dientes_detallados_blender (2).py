"""Craneos dentales: adulto a la izquierda e infantil a la derecha.

Blender: Scripting > Open > este archivo > Run Script (Alt+P).
No requiere complementos, descargas, modelos ni texturas externas.
Crea su propia escena y no elimina objetos de otras escenas.
La mandibula y sus dientes se animan juntos. Espacio: reproducir la apertura.
32 piezas permanentes y 20 temporales, con raices curvas y apices redondeados.
Los huesos se fusionan y se tallan alveolos para insertar cada diente.

Es una aproximacion artistica, no un escaneo ni un modelo clinico.
Las piezas multirradiculares contienen superficies superpuestas: no estan
preparadas para fabricar protesis ni para impresion 3D sin retopologia.

Materiales: https://docs.blender.org/manual/en/latest/render/shader_nodes/
shader/principled.html
"""

import math
from dataclasses import dataclass

import bpy
import bmesh
from mathutils import Matrix, Vector


# -------------------------- CONFIGURACION --------------------------
CALIDAD = "ALTA"                 # MEDIA, ALTA o ULTRA
DISTRIBUCION = "ARCADAS"
INCLUIR_CRANEO = True
TRANSPARENCIA_CRANEO = 0.72      # 0: opaco; 1: totalmente invisible
COLOR_CRANEO = (0.80, 0.84, 0.86, 1.0)
ANIMAR_APERTURA = True
MOSTRAR_RAICES = True
MOSTRAR_ROTULOS = True
INCLUIR_MUELAS_DEL_JUICIO = True
ESPACIO_ENTRE_DIENTES_MM = 0.14
SEPARACION_ARCADAS_MM = 1.6
APERTURA_INFERIOR_GRADOS = 12.0
SEPARACION_CRANEOS_MM = 210.0
FONDO_TRANSPARENTE = False       # Solo el fondo del render; el hueso sigue transparente
RESOLUCION_X = 2400
RESOLUCION_Y = 1600
MUESTRAS_RENDER = 96
NOMBRE_ESCENA = "Adulto_Izquierda_Infantil_Derecha"
# ------------------------------------------------------------------

MM = 0.001
TAG = "dientes_procedurales_v1"
TAU = 2.0 * math.pi


@dataclass(frozen=True)
class Forma:
    tipo: str
    ancho: float
    fondo: float
    alto: float
    largo_raiz: float
    superior: bool
    posicion: int
    temporal: bool = False


# Dimensiones orientativas en mm; no son medidas de un paciente.
DIMENSIONES = {
    True: [
        (8.5, 7.0, 10.5, 13.0), (6.5, 6.5, 9.4, 13.4),
        (7.7, 8.2, 10.5, 17.0), (7.0, 8.6, 8.8, 13.0),
        (6.8, 8.5, 8.3, 13.2), (10.5, 11.0, 7.7, 12.3),
        (9.6, 10.9, 7.1, 11.7), (9.3, 10.0, 6.7, 10.7),
    ],
    False: [
        (5.3, 5.9, 9.1, 12.3), (5.8, 6.3, 9.6, 13.3),
        (6.7, 7.6, 10.6, 15.5), (7.0, 7.9, 8.4, 13.5),
        (7.4, 8.4, 8.0, 13.7), (11.0, 10.2, 7.7, 12.7),
        (10.5, 10.0, 7.3, 12.2), (9.8, 9.1, 7.0, 10.7),
    ],
}

DIMENSIONES_TEMPORALES = {
    True: [(6.5, 5.0, 6.3, 10.0), (5.0, 4.9, 5.6, 9.6),
           (6.5, 6.6, 6.5, 11.0), (7.2, 8.5, 5.5, 9.3),
           (9.0, 9.7, 5.5, 9.9)],
    False: [(4.1, 4.1, 5.0, 8.3), (4.6, 4.5, 5.3, 8.8),
            (5.4, 5.4, 6.2, 10.2), (7.7, 6.8, 5.4, 9.3),
            (9.7, 8.8, 5.1, 9.7)],
}

NOMBRES_TEMPORALES = ["Incisivo_central", "Incisivo_lateral", "Canino",
                      "Primer_molar", "Segundo_molar"]

NOMBRES = ["Incisivo_central", "Incisivo_lateral", "Canino",
           "Primer_premolar", "Segundo_premolar", "Primer_molar",
           "Segundo_molar", "Tercer_molar"]

RES = {
    "MEDIA": (80, 28, 36, 32, 48),
    "ALTA": (128, 42, 56, 48, 64),
    "ULTRA": (176, 64, 84, 72, 96),
}


def mezclar(a, b, t):
    return a + (b - a) * t


def limitar(x, a=0.0, b=1.0):
    return max(a, min(b, x))


def suave(x):
    x = limitar(x)
    return x * x * (3.0 - 2.0 * x)


def perfil(t, puntos):
    # Hermite monotono evita los escalones de una interpolacion por tramos.
    pendientes = [(vb - va) / (tb - ta)
                  for (ta, va), (tb, vb) in zip(puntos, puntos[1:])]

    def derivada(i):
        if i == 0:
            return pendientes[0]
        if i == len(puntos) - 1:
            return pendientes[-1]
        a, b = pendientes[i - 1], pendientes[i]
        return 0.0 if a * b <= 0 else 2.0 * a * b / (a + b)

    for i, ((ta, va), (tb, vb)) in enumerate(zip(puntos, puntos[1:])):
        if t <= tb:
            u = limitar((t - ta) / (tb - ta))
            h = tb - ta
            return ((2 * u ** 3 - 3 * u ** 2 + 1) * va
                    + (u ** 3 - 2 * u ** 2 + u) * h * derivada(i)
                    + (-2 * u ** 3 + 3 * u ** 2) * vb
                    + (u ** 3 - u ** 2) * h * derivada(i + 1))
    return puntos[-1][1]


def gauss(x, y, cx, cy, sx, sy):
    return math.exp(-((x - cx) / sx) ** 2 - ((y - cy) / sy) ** 2)


def superelipse(a, exponente):
    c, s = math.cos(a), math.sin(a)
    p = 2.0 / exponente
    return math.copysign(abs(c) ** p, c), math.copysign(abs(s) ** p, s)


def distancia_segmento(x, y, a, b):
    dx, dy = b[0] - a[0], b[1] - a[1]
    den = dx * dx + dy * dy
    t = limitar(((x - a[0]) * dx + (y - a[1]) * dy) / den) if den else 0.0
    return math.hypot(x - a[0] - t * dx, y - a[1] - t * dy)


def distancia_surcos(x, y, caminos):
    return min(distancia_segmento(x, y, a, b)
               for camino in caminos for a, b in zip(camino, camino[1:]))


def surcos(forma):
    if forma.tipo == "premolar":
        return [[(-0.72, 0.02), (-0.40, 0.025), (0.00, -0.02),
                 (0.40, 0.04), (0.72, 0.03)],
                [(-0.44, 0.025), (-0.69, 0.43)],
                [(0.42, 0.04), (0.68, -0.40)]]
    if forma.superior:
        return [[(-0.78, -0.035), (-0.42, -0.055), (-0.10, 0.06),
                 (0.30, 0.035), (0.70, 0.10)],
                [(-0.10, 0.06), (-0.06, 0.46), (0.02, 0.88)],
                [(0.30, 0.035), (0.35, -0.40), (0.53, -0.82)],
                [(-0.42, -0.055), (-0.69, -0.43)]]
    caminos = [[(-0.81, 0.02), (-0.47, 0.05), (-0.08, -0.035),
                (0.35, -0.06), (0.79, 0.025)],
               [(-0.08, -0.035), (-0.10, -0.48), (-0.13, -0.88)]]
    if forma.posicion == 6 or (forma.temporal and forma.posicion == 5):
        caminos += [[(-0.24, 0.035), (-0.23, 0.46), (-0.25, 0.88)],
                    [(0.36, -0.06), (0.32, 0.43), (0.38, 0.86)]]
    else:
        caminos += [[(-0.08, -0.035), (0.04, 0.45), (0.02, 0.88)]]
    return caminos


def cuspides(forma):
    # x: mesiodistal; +y: vestibular; -y: palatino/lingual.
    if forma.temporal and forma.posicion == 4:
        if forma.superior:
            return [(-0.43, 0.44, 0.265, 0.43, 0.43),
                    (0.40, 0.42, 0.205, 0.40, 0.42),
                    (-0.34, -0.45, 0.28, 0.49, 0.43),
                    (0.44, -0.42, 0.12, 0.37, 0.37)]
        return [(-0.43, 0.46, 0.255, 0.43, 0.43),
                (0.44, 0.43, 0.20, 0.41, 0.41),
                (-0.43, -0.44, 0.285, 0.43, 0.43),
                (0.43, -0.41, 0.19, 0.40, 0.41)]
    if forma.tipo == "premolar":
        lingual = 0.255 if forma.superior else 0.175
        if not forma.superior and forma.posicion == 4:
            lingual = 0.13
        if not forma.superior and forma.posicion == 5:
            return [(0.0, 0.47, 0.30, 0.58, 0.35),
                    (-0.32, -0.48, 0.21, 0.36, 0.34),
                    (0.34, -0.43, 0.19, 0.34, 0.34)]
        return [(-0.04, 0.48, 0.30, 0.59, 0.35),
                (0.04, -0.47, lingual, 0.55, 0.37)]
    if forma.superior:
        c = [(-0.45, 0.46, 0.265, 0.39, 0.40),
             (0.44, 0.46, 0.225, 0.38, 0.39),
             (-0.40, -0.44, 0.285, 0.43, 0.42),
             (0.43, -0.43, 0.205, 0.36, 0.39)]
        if forma.posicion == 6 or (forma.temporal and forma.posicion == 5):
            c += [(-0.59, -0.79, 0.085, 0.21, 0.20)]
        if forma.posicion == 8:
            c[-1] = (0.36, -0.38, 0.145, 0.39, 0.40)
        return c
    if forma.posicion == 6 or (forma.temporal and forma.posicion == 5):
        return [(-0.54, 0.44, 0.235, 0.35, 0.40),
                (0.04, 0.47, 0.23, 0.31, 0.38),
                (0.61, 0.35, 0.185, 0.29, 0.35),
                (-0.44, -0.45, 0.285, 0.41, 0.40),
                (0.38, -0.44, 0.27, 0.40, 0.40)]
    return [(-0.43, 0.45, 0.245, 0.40, 0.40),
            (0.44, 0.44, 0.225, 0.39, 0.40),
            (-0.43, -0.46, 0.28, 0.40, 0.40),
            (0.44, -0.45, 0.26, 0.39, 0.40)]


def altura_oclusal(x, y, forma, c, caminos):
    if forma.tipo == "incisivo":
        mamelones = 0.009 * math.cos(3.0 * math.pi * x) * (1.0 - x * x)
        return forma.alto * (0.961 + 0.030 * (1.0 - x * x)
                              - 0.020 * y * y + mamelones), 0.0
    if forma.tipo == "canino":
        punta = max(0.0, 1.0 - x * x) ** 1.8
        return forma.alto * (0.735 + 0.275 * punta * (1.0 - 0.23 * y * y)), 0.0
    r = max(abs(x), abs(y))
    z = 0.70 + sum(amp * gauss(x, y, cx, cy, sx, sy)
                   for cx, cy, amp, sx, sy in c)
    # Reborde marginal, fosas y surcos tallados en la geometria.
    z += 0.036 * math.exp(-((r - 0.85) / 0.12) ** 2)
    if forma.superior and forma.tipo == "molar":
        z += 0.026 * gauss(x, y, 0.06, -0.15, 0.50, 0.16)
    d = distancia_surcos(x, y, caminos)
    mascara = math.exp(-(d / 0.034) ** 2) * (1.0 - suave((r - 0.82) / 0.16))
    z -= 0.025 * mascara
    for px, py in [(-0.43, 0.02), (0.39, 0.015)]:
        z -= 0.022 * gauss(x, y, px, py, 0.12, 0.11)
    return forma.alto * z, mascara


def color_esmalte(t, surco=0.0, variacion=0.0):
    cervical = (0.63, 0.555, 0.415)
    cuerpo = (0.84, 0.81, 0.72)
    borde = (0.92, 0.925, 0.895)
    a = suave(t / 0.52)
    b = 0.64 * suave((t - 0.65) / 0.35)
    color = [mezclar(mezclar(cervical[i], cuerpo[i], a), borde[i], b)
             for i in range(3)]
    # La tincion es leve: un surco natural no es una linea negra.
    tinte = (0.54, 0.44, 0.27)
    return tuple(limitar(mezclar(v, tinte[i], 0.14 * surco) + variacion)
                 for i, v in enumerate(color)) + (1.0,)


def color_raiz(t, a=0.0):
    color = (0.66, 0.59, 0.44)
    ruido = 0.015 * math.sin(5.0 * a + 7.0 * t)
    return tuple(limitar(v + ruido + 0.035 * t) for v in color) + (1.0,)


class Malla:
    def __init__(self):
        self.vertices = []
        self.colores = []
        self.caras = []
        self.materiales = []

    def vertice(self, co, color):
        i = len(self.vertices)
        self.vertices.append(tuple(v * MM for v in co))
        self.colores.append(color)
        return i

    def cara(self, indices, material=0):
        self.caras.append(tuple(indices))
        self.materiales.append(material)

    def unir(self, a, b, material=0):
        for j in range(len(a)):
            k = (j + 1) % len(a)
            self.cara((a[j], a[k], b[k], b[j]), material)

    def tapar(self, anillo, centro, color, material=0):
        i = self.vertice(centro, color)
        for j in range(len(anillo)):
            self.cara((anillo[j], anillo[(j + 1) % len(anillo)], i), material)

    def objeto(self, nombre, coleccion, materiales):
        mesh = bpy.data.meshes.new(nombre + "_Mesh")
        mesh["generador"] = TAG
        mesh.from_pydata(self.vertices, [], self.caras)
        mesh.update()
        bm = bmesh.new()
        bm.from_mesh(mesh)
        bmesh.ops.recalc_face_normals(bm, faces=list(bm.faces))
        bm.to_mesh(mesh)
        bm.free()
        colores = mesh.color_attributes.new(name="TinteDental", type="FLOAT_COLOR", domain="POINT")
        colores.data.foreach_set("color", [v for c in self.colores for v in c])
        for mat in materiales:
            mesh.materials.append(mat)
        for poly, indice in zip(mesh.polygons, self.materiales):
            poly.use_smooth = True
            poly.material_index = indice
        obj = bpy.data.objects.new(nombre, mesh)
        obj["generador"] = TAG
        coleccion.objects.link(obj)
        return obj


def construir_corona(malla, forma, n, niveles, radiales, variacion):
    anterior = forma.tipo in ("incisivo", "canino")
    exponente = 2.65 if anterior else (2.7 if forma.tipo == "molar" else 2.35)
    c = cuspides(forma) if not anterior else []
    c = [(cx * 1.04, cy * 1.18, amp, sx * 1.04, sy * 1.05)
         for cx, cy, amp, sx, sy in c]
    caminos = surcos(forma) if not anterior else []
    previo = None
    cervical = None
    for k in range(niveles + 1):
        t = k / niveles
        if forma.tipo == "incisivo":
            rx = perfil(t, [(0, 0.69), (0.22, 0.87), (0.58, 0.98), (0.86, 1.0), (1, 0.95)])
            ry = perfil(t, [(0, 0.68), (0.23, 0.94), (0.48, 0.94), (0.75, 0.62), (1, 0.16)])
        elif forma.tipo == "canino":
            rx = perfil(t, [(0, 0.67), (0.29, 0.91), (0.57, 1.0), (1, 0.85)])
            ry = perfil(t, [(0, 0.69), (0.26, 0.97), (0.55, 0.91), (1, 0.48)])
        else:
            rx = perfil(t, [(0, 0.70), (0.33, 0.92), (0.63, 1.0), (1, 0.92)])
            ry = perfil(t, [(0, 0.68), (0.32, 0.93), (0.62, 1.0), (1, 0.92)])
        if forma.temporal:
            estrechamiento = 1.0 - 0.085 * (1.0 - suave(t / 0.32))
            rx *= estrechamiento
            ry *= estrechamiento
        anillo = []
        for j in range(n):
            a = TAU * j / n
            u, v = superelipse(a, exponente)
            ondulacion = 1.0 + 0.012 * math.sin(3.0 * a + 0.4) * math.sin(math.pi * t)
            periquimatias = 0.0009 * math.sin(100.0 * t + 2.0 * a) * math.sin(math.pi * t)
            x = 0.5 * forma.ancho * rx * u * (ondulacion + periquimatias)
            y = 0.5 * forma.fondo * ry * v * (ondulacion + periquimatias)
            if anterior:
                lingual = max(0.0, -math.sin(a)) ** 4
                fosa = 0.15 * math.exp(-((t - 0.62) / 0.22) ** 2) * max(0.0, 1.0 - u * u)
                cingulo = 0.12 * math.exp(-((t - 0.22) / 0.15) ** 2)
                if forma.tipo == "canino":
                    fosa *= 0.62
                y *= 1.0 + lingual * (cingulo - fosa)
                lobulos = sum(math.exp(-((u - centro) / 0.22) ** 2)
                              for centro in (-0.55, 0.0, 0.55))
                y *= 1.0 + 0.025 * max(0.0, math.sin(a)) ** 4 * lobulos * math.sin(math.pi * t)
            elif forma.tipo == "molar":
                vestibular = max(0.0, math.sin(a)) ** 8
                cinco = forma.posicion == 6 or (forma.temporal and forma.posicion == 5)
                centros = (-0.25, 0.33) if not forma.superior and cinco else (0.0,)
                depresion = sum(gauss(u, t, centro, 0.92, 0.065, 0.26) for centro in centros)
                y *= 1.0 - 0.045 * vestibular * depresion
            if forma.superior and forma.tipo == "molar":
                x += 0.035 * forma.ancho * v * suave(t)
            z_cej = 0.23 * math.cos(2.0 * a) - 0.07 * math.sin(a)
            z_borde, _ = altura_oclusal(u, v, forma, c, caminos)
            z = mezclar(z_cej, z_borde, t)
            anillo.append(malla.vertice((x, y, z), color_esmalte(t, variacion=variacion)))
        if previo is not None:
            malla.unir(previo, anillo)
        else:
            cervical = anillo
        previo = anillo

    # El casquete oclusal comparte su contorno con el cuerpo de la corona.
    exterior = [tuple(v / MM for v in malla.vertices[i]) for i in previo]
    for k in range(1, radiales):
        r = 1.0 - k / radiales
        anillo = []
        for j in range(n):
            u, v = superelipse(TAU * j / n, exponente)
            z, mascara = altura_oclusal(r * u, r * v, forma, c, caminos)
            x, y = exterior[j][0] * r, exterior[j][1] * r
            anillo.append(malla.vertice((x, y, z), color_esmalte(1.0, mascara, variacion)))
        malla.unir(previo, anillo)
        previo = anillo
    z, mascara = altura_oclusal(0.0, 0.0, forma, c, caminos)
    malla.tapar(previo, (0.0, 0.0, z), color_esmalte(1.0, mascara, variacion))
    return cervical


def perfil_raiz(t):
    # Conserva el cuerpo hasta el tercio apical y termina en un casquete curvo.
    if t >= 0.90:
        return 0.43 * math.sqrt(max(0.0, (1.0 - t) / 0.10))
    return perfil(t, [(0, 1.0), (0.18, 0.985), (0.42, 0.93),
                       (0.64, 0.80), (0.78, 0.66), (0.90, 0.43)])


def muestras_raiz(niveles):
    cuerpo = int(niveles * 0.72)
    resultado = [0.90 * k / cuerpo for k in range(cuerpo + 1)]
    resto = niveles - cuerpo
    resultado.extend(0.90 + 0.10 * math.sin(0.5 * math.pi * k / resto)
                     for k in range(1, resto))
    return resultado


def raiz_unica(malla, forma, cervical, niveles):
    n = len(cervical)
    inicio = [tuple(v / MM for v in malla.vertices[i]) for i in cervical]
    previo = cervical
    for t in muestras_raiz(niveles)[1:]:
        radio = perfil_raiz(t)
        anillo = []
        for j, co in enumerate(inicio):
            a = TAU * j / n
            acanalado = 1.0 - 0.050 * math.cos(2.0 * a) ** 8 * math.sin(math.pi * t)
            x = co[0] * radio * acanalado + 1.15 * t * t
            y = co[1] * radio * acanalado - 0.55 * t * t
            z = -forma.largo_raiz * t + co[2] * (1.0 - suave(t / 0.16))
            anillo.append(malla.vertice((x, y, z), color_raiz(t, a)))
        malla.unir(previo, anillo, 1)
        previo = anillo
    malla.tapar(previo, (1.15, -0.55, -forma.largo_raiz), color_raiz(1), 1)


def tubo_raiz(malla, origen, final, ancho, fondo, n, niveles, curva=0.4):
    ox, oy, oz = origen
    fx, fy, fz = final
    previo = None
    primero = None
    for t in muestras_raiz(niveles):
        factor = perfil_raiz(t)
        cx = mezclar(ox, fx, t) + curva * math.sin(math.pi * t)
        cy = mezclar(oy, fy, t) - 0.25 * math.sin(math.pi * t)
        cz = mezclar(oz, fz, t)
        anillo = []
        for j in range(n):
            a = TAU * j / n
            surco = 1.0 - 0.07 * math.cos(2.0 * a) ** 10 * math.sin(math.pi * t)
            x = cx + ancho * math.cos(a) * factor * surco
            y = cy + fondo * math.sin(a) * factor * surco
            anillo.append(malla.vertice((x, y, cz), color_raiz(t, a)))
        if previo is None:
            primero = anillo
        else:
            malla.unir(previo, anillo, 1)
        previo = anillo
    malla.tapar(primero, origen, color_raiz(0), 1)
    malla.tapar(previo, final, color_raiz(1), 1)


def raices_multiples(malla, forma, cervical, niveles, n):
    largo = forma.largo_raiz
    tronco = 1.55 if forma.temporal else 2.25 if forma.tipo == "molar" else 1.7
    inicio = [tuple(v / MM for v in malla.vertices[i]) for i in cervical]
    previo = cervical
    for k in range(1, 13):
        t = k / 12.0
        anillo = [malla.vertice((co[0] * mezclar(1.0, 0.78, t),
                                co[1] * mezclar(1.0, 0.78, t),
                                mezclar(co[2], -tronco, t)), color_raiz(t * 0.12))
                  for co in inicio]
        malla.unir(previo, anillo, 1)
        previo = anillo
    malla.tapar(previo, (0, 0, -tronco), color_raiz(0.12), 1)
    if forma.tipo == "premolar":
        recetas = [((0.0, 1.2, -1.15), (0.6, 2.15, -largo), 1.55, 1.72),
                   ((0.0, -1.2, -1.15), (0.8, -2.30, -largo * 0.95), 1.50, 1.65)]
    elif forma.superior:
        recetas = [((-1.70, 1.25, -1.45), (-2.30, 3.10, -largo), 1.82, 1.62),
                   ((1.75, 1.15, -1.45), (3.25, 2.90, -largo * 0.88), 1.65, 1.53),
                   ((0.0, -1.75, -1.45), (0.50, -4.00, -largo * 1.05), 2.05, 1.88)]
    else:
        recetas = [((-2.0, 0.0, -1.45), (-3.45, -0.35, -largo), 1.94, 2.25),
                   ((2.0, 0.0, -1.45), (3.70, 0.20, -largo * 0.94), 1.78, 2.12)]
    # Los terceros molares tienen menos divergencia en esta variante artistica.
    for origen, final, ancho, fondo in recetas:
        if forma.temporal:
            sx = forma.ancho / (10.5 if forma.superior else 11.0)
            sy = forma.fondo / (11.0 if forma.superior else 10.2)
            origen = (origen[0] * sx, origen[1] * sy, origen[2] * 0.75)
            final = (final[0] * sx * 1.12, final[1] * sy * 1.16, final[2])
            ancho *= sx * 0.90
            fondo *= sy * 0.90
        if forma.posicion == 8:
            final = (final[0] * 0.65, final[1] * 0.68, final[2])
        tubo_raiz(malla, origen, final, ancho, fondo, n, niveles)
    return len(recetas)


def entrada(nodo, nombres, valor):
    for nombre in nombres:
        socket = nodo.inputs.get(nombre)
        if socket is not None:
            socket.default_value = valor
            return


def material_dental(nombre, raiz=False):
    mat = bpy.data.materials.new(nombre)
    mat["generador"] = TAG
    mat.use_nodes = True
    mat.diffuse_color = (0.74, 0.62, 0.40, 1.0) if raiz else (0.89, 0.86, 0.77, 1.0)
    nodos = mat.node_tree.nodes
    enlaces = mat.node_tree.links
    nodos.clear()
    salida = nodos.new("ShaderNodeOutputMaterial")
    salida.location = (800, 80)
    shader = nodos.new("ShaderNodeBsdfPrincipled")
    shader.location = (520, 80)
    entrada(shader, ["IOR"], 1.50 if raiz else 1.48)
    entrada(shader, ["Roughness"], 0.36 if raiz else 0.23)
    entrada(shader, ["Specular IOR Level", "Specular"], 0.35)
    entrada(shader, ["Coat Weight", "Clearcoat"], 0.08 if raiz else 0.28)
    entrada(shader, ["Coat Roughness", "Clearcoat Roughness"], 0.12)
    entrada(shader, ["Subsurface Weight", "Subsurface"], 0.025 if raiz else 0.07)
    entrada(shader, ["Subsurface Radius"], (0.00045, 0.00025, 0.00012))
    entrada(shader, ["Subsurface Scale"], 1.0)
    entrada(shader, ["Transmission Weight", "Transmission"], 0.0 if raiz else 0.025)
    atributo = nodos.new("ShaderNodeAttribute")
    atributo.attribute_name = "TinteDental"
    atributo.location = (-440, 240)
    coord = nodos.new("ShaderNodeTexCoord")
    coord.location = (-650, -180)
    ruido = nodos.new("ShaderNodeTexNoise")
    ruido.location = (-430, -160)
    ruido.inputs["Scale"].default_value = 145.0 if raiz else 220.0
    ruido.inputs["Detail"].default_value = 3.0
    ruido.inputs["Roughness"].default_value = 0.62
    enlaces.new(coord.outputs["Generated"], ruido.inputs["Vector"])
    mezcla = nodos.new("ShaderNodeMixRGB")
    mezcla.blend_type = "MULTIPLY"
    mezcla.location = (200, 210)
    mezcla.inputs[0].default_value = 0.055
    enlaces.new(atributo.outputs["Color"], mezcla.inputs[1])
    enlaces.new(ruido.outputs["Color"], mezcla.inputs[2])
    enlaces.new(mezcla.outputs[0], shader.inputs["Base Color"])
    relieve = nodos.new("ShaderNodeBump")
    relieve.location = (220, -140)
    relieve.inputs["Strength"].default_value = 0.12 if raiz else 0.075
    relieve.inputs["Distance"].default_value = (0.025 if raiz else 0.012) * MM
    enlaces.new(ruido.outputs["Fac"], relieve.inputs["Height"])
    enlaces.new(relieve.outputs["Normal"], shader.inputs["Normal"])
    enlaces.new(shader.outputs["BSDF"], salida.inputs["Surface"])
    return mat


def material_craneo(mandibula=False):
    mat = bpy.data.materials.new("Hueso_mandibular_transparente" if mandibula else "Hueso_craneal_transparente")
    mat["generador"] = TAG
    mat.use_nodes = True
    mat.diffuse_color = (*COLOR_CRANEO[:3], 1.0 - TRANSPARENCIA_CRANEO)
    if hasattr(mat, "surface_render_method"):
        mat.surface_render_method = "DITHERED"
    elif hasattr(mat, "blend_method"):
        mat.blend_method = "HASHED"
    n, l = mat.node_tree.nodes, mat.node_tree.links
    n.clear()
    salida = n.new("ShaderNodeOutputMaterial")
    salida.location = (880, 140)
    transparente = n.new("ShaderNodeBsdfTransparent")
    transparente.inputs["Color"].default_value = (1, 1, 1, 1)
    transparente.location = (170, -90)
    hueso = n.new("ShaderNodeBsdfPrincipled")
    hueso.location = (160, 180)
    entrada(hueso, ["Base Color"], COLOR_CRANEO)
    entrada(hueso, ["Roughness"], 0.46)
    entrada(hueso, ["Coat Weight", "Clearcoat"], 0.06)
    entrada(hueso, ["Specular IOR Level", "Specular"], 0.30)
    entrada(hueso, ["Emission Color", "Emission"], COLOR_CRANEO)
    entrada(hueso, ["Emission Strength"], 0.025)
    textura = n.new("ShaderNodeTexNoise")
    textura.inputs["Scale"].default_value = 1450.0
    textura.inputs["Detail"].default_value = 2.5
    relieve = n.new("ShaderNodeBump")
    relieve.inputs["Strength"].default_value = 0.17
    relieve.inputs["Distance"].default_value = 0.07 * MM
    l.new(textura.outputs["Fac"], relieve.inputs["Height"])
    l.new(relieve.outputs["Normal"], hueso.inputs["Normal"])
    peso = n.new("ShaderNodeLayerWeight")
    peso.location = (-710, 330)
    peso.inputs["Blend"].default_value = 0.3
    borde = n.new("ShaderNodeMath")
    borde.operation = "MULTIPLY_ADD"
    borde.use_clamp = True
    borde.location = (-440, 310)
    opacidad = 1.0 - TRANSPARENCIA_CRANEO
    borde.inputs[1].default_value = 0.9 * opacidad * TRANSPARENCIA_CRANEO
    borde.inputs[2].default_value = opacidad
    l.new(peso.outputs["Fresnel"], borde.inputs[0])
    geometria = n.new("ShaderNodeNewGeometry")
    geometria.location = (-710, 100)
    posterior = n.new("ShaderNodeMath")
    posterior.operation = "MULTIPLY_ADD"
    posterior.location = (-440, 70)
    posterior.inputs[1].default_value = -0.90
    posterior.inputs[2].default_value = 1.0
    l.new(geometria.outputs["Backfacing"], posterior.inputs[0])
    alpha = n.new("ShaderNodeMath")
    alpha.operation = "MULTIPLY"
    alpha.location = (-190, 300)
    l.new(borde.outputs[0], alpha.inputs[0])
    l.new(posterior.outputs[0], alpha.inputs[1])
    coordenadas = n.new("ShaderNodeTexCoord")
    ejes = n.new("ShaderNodeSeparateXYZ")
    l.new(coordenadas.outputs["Generated"], ejes.inputs[0])
    region = n.new("ShaderNodeMapRange")
    region.clamp = True
    region.inputs["From Min"].default_value = 0.44
    region.inputs["From Max"].default_value = 0.65
    region.inputs["To Min"].default_value = 1.0
    region.inputs["To Max"].default_value = 1.0 if mandibula else 1.0 - 0.82 * limitar(TRANSPARENCIA_CRANEO / 0.72)
    l.new(ejes.outputs["Z"], region.inputs["Value"])
    cortical = n.new("ShaderNodeMath")
    cortical.operation = "MULTIPLY"
    l.new(alpha.outputs[0], cortical.inputs[0])
    l.new(region.outputs["Result"], cortical.inputs[1])
    mezcla = n.new("ShaderNodeMixShader")
    mezcla.location = (490, 210)
    l.new(cortical.outputs[0], mezcla.inputs[0])
    l.new(transparente.outputs[0], mezcla.inputs[1])
    l.new(hueso.outputs[0], mezcla.inputs[2])
    # La transparencia directa permite inspeccionar los dientes sin refraccion.
    rayos = n.new("ShaderNodeLightPath")
    rayos.location = (210, -260)
    sombras = n.new("ShaderNodeMixShader")
    sombras.location = (690, 140)
    l.new(rayos.outputs["Is Shadow Ray"], sombras.inputs[0])
    l.new(mezcla.outputs[0], sombras.inputs[1])
    l.new(transparente.outputs[0], sombras.inputs[2])
    l.new(sombras.outputs[0], salida.inputs["Surface"])
    return mat


def objeto_hueso(malla, nombre, col, material, padre=None):
    obj = malla.objeto(nombre, col, [material])
    obj["estructura"] = "Hueso"
    obj["uso"] = "Craneo procedural artistico; no clinico"
    obj.color = (*COLOR_CRANEO[:3], 1.0 - TRANSPARENCIA_CRANEO)
    if hasattr(obj, "show_transparent"):
        obj.show_transparent = True
    if padre is not None:
        obj.parent = padre
        obj.location = -padre.location
    return obj


def elipsoide_hueso(nombre, centro, radios, col, material, padre=None,
                    segmentos=80, niveles=52):
    malla = Malla()
    blanco = (1, 1, 1, 1)
    cx, cy, cz = centro
    rx, ry, rz = radios
    superior = malla.vertice((cx, cy, cz + rz), blanco)
    previo = None
    for k in range(1, niveles):
        t = math.pi * k / niveles
        anillo = []
        for j in range(segmentos):
            a = TAU * j / segmentos
            co = (cx + rx * math.sin(t) * math.cos(a),
                  cy + ry * math.sin(t) * math.sin(a), cz + rz * math.cos(t))
            anillo.append(malla.vertice(co, blanco))
        if previo is None:
            for j in range(segmentos):
                malla.cara((superior, anillo[j], anillo[(j + 1) % segmentos]))
        else:
            malla.unir(previo, anillo)
        previo = anillo
    malla.tapar(previo, (cx, cy, cz - rz), blanco)
    return objeto_hueso(malla, nombre, col, material, padre)


def loft_hueso(nombre, cortes, col, material, segmentos=96, niveles=64, casquete=False):
    # cortes: (z, radio_x, radio_y, centro_y), en mm.
    malla = Malla()
    blanco = (1, 1, 1, 1)
    previo = None
    primero = None
    z0, z1 = cortes[0][0], cortes[-1][0]
    for k in range(niveles + 1):
        z = mezclar(z0, z1, k / niveles)
        rx = perfil(z, [(c[0], c[1]) for c in cortes])
        ry = perfil(z, [(c[0], c[2]) for c in cortes])
        cy = perfil(z, [(c[0], c[3]) for c in cortes])
        if casquete and z >= cortes[-3][0]:
            base = cortes[-3]
            t = (z - base[0]) / (z1 - base[0] + 0.06)
            factor = math.sqrt(max(0.0, 1.0 - t * t))
            rx, ry, cy = base[1] * factor, base[2] * factor, base[3]
        anillo = [malla.vertice((rx * math.cos(TAU * j / segmentos),
                                 cy + ry * math.sin(TAU * j / segmentos), z), blanco)
                  for j in range(segmentos)]
        if previo is None:
            primero = anillo
        else:
            malla.unir(previo, anillo)
        previo = anillo
    malla.tapar(primero, (0, cortes[0][3], z0), blanco)
    malla.tapar(previo, (0, cortes[-1][3], z1), blanco)
    return objeto_hueso(malla, nombre, col, material)


def cavidad_orbitaria(nombre, lado, col, material):
    # Apertura de contorno trapezoidal redondeado, sin el aspecto de un aro.
    malla = Malla()
    primero = previo = None
    for k in range(1, 40):
        t = math.pi * k / 40
        y = -45 + 35 * math.cos(t)
        anillo = []
        for j in range(80):
            u, v = superelipse(TAU * j / 80, 2.55)
            x = lado * (30 + 19.8 * math.sin(t) * u)
            z = 73 + 15.8 * math.sin(t) * v + 1.1 * u
            anillo.append(malla.vertice((x, y, z), (1, 1, 1, 1)))
        if previo is None:
            primero = anillo
        else:
            malla.unir(previo, anillo)
        previo = anillo
    malla.tapar(primero, (lado * 30, -10, 73), (1, 1, 1, 1))
    malla.tapar(previo, (lado * 30, -80, 73), (1, 1, 1, 1))
    return objeto_hueso(malla, nombre, col, material)


def muestras_hueso(nudos, pasos=12, cerrada=False):
    # Catmull-Rom transporta tambien los dos radios de la seccion.
    cantidad = len(nudos) if cerrada else len(nudos) - 1
    resultado = []
    for i in range(cantidad):
        ids = [(i - 1) % len(nudos), i, (i + 1) % len(nudos), (i + 2) % len(nudos)]
        if not cerrada:
            ids = [max(0, min(len(nudos) - 1, x)) for x in (i - 1, i, i + 1, i + 2)]
        p0, p1, p2, p3 = [nudos[x] for x in ids]
        for k in range(pasos):
            t = k / pasos
            resultado.append(tuple(0.5 * ((2 * b) + (-a + c) * t
                                          + (2 * a - 5 * b + 4 * c - d) * t * t
                                          + (-a + 3 * b - 3 * c + d) * t ** 3)
                                   for a, b, c, d in zip(p0, p1, p2, p3)))
    if not cerrada:
        resultado.append(nudos[-1])
    return resultado


def tubo_hueso(nombre, nudos, col, material, padre=None, cerrada=False,
              pasos=12, segmentos=24):
    puntos = muestras_hueso(nudos, pasos, cerrada)
    malla = Malla()
    blanco = (1, 1, 1, 1)
    previo = None
    primero = None
    normal = None
    ultima_tangente = None
    for i, punto in enumerate(puntos):
        p = Vector(punto[:3])
        anterior = Vector(puntos[(i - 1) % len(puntos)][:3]) if cerrada else Vector(puntos[max(0, i - 1)][:3])
        siguiente = Vector(puntos[(i + 1) % len(puntos)][:3]) if cerrada else Vector(puntos[min(len(puntos) - 1, i + 1)][:3])
        tangente = (siguiente - anterior).normalized()
        if normal is None:
            ref = Vector((0, 1, 0)) if abs(tangente.z) > 0.9 else Vector((0, 0, 1))
            normal = tangente.cross(ref).normalized()
        else:
            normal = ultima_tangente.rotation_difference(tangente) @ normal
            normal = (normal - tangente * normal.dot(tangente)).normalized()
        binormal = tangente.cross(normal).normalized()
        anillo = []
        for j in range(segmentos):
            a = TAU * j / segmentos
            co = p + normal * punto[3] * math.cos(a) + binormal * punto[4] * math.sin(a)
            anillo.append(malla.vertice(co, blanco))
        if previo is None:
            primero = anillo
        else:
            malla.unir(previo, anillo)
        previo = anillo
        ultima_tangente = tangente
    if cerrada:
        malla.unir(previo, primero)
    else:
        malla.tapar(primero, puntos[0][:3], blanco)
        malla.tapar(previo, puntos[-1][:3], blanco)
    return objeto_hueso(malla, nombre, col, material, padre)


def quitar_temporal(obj):
    mesh = obj.data
    bpy.data.objects.remove(obj, do_unlink=True)
    if mesh.users == 0:
        bpy.data.meshes.remove(mesh)


def restar_volumen(obj, cortador, escena):
    modificador = obj.modifiers.new("Cavidad_anatomica", "BOOLEAN")
    modificador.operation = "DIFFERENCE"
    modificador.solver = "EXACT"
    modificador.object = cortador
    escena.view_layers[0].update()
    grafo = bpy.context.evaluated_depsgraph_get()
    resultado = bpy.data.meshes.new_from_object(obj.evaluated_get(grafo), depsgraph=grafo)
    resultado["generador"] = TAG
    anterior = obj.data
    obj.modifiers.clear()
    obj.data = resultado
    if anterior.users == 0:
        bpy.data.meshes.remove(anterior)
    for poligono in resultado.polygons:
        poligono.use_smooth = True


def aplicar_modificadores(obj, escena):
    escena.view_layers[0].update()
    grafo = bpy.context.evaluated_depsgraph_get()
    mesh = bpy.data.meshes.new_from_object(obj.evaluated_get(grafo), depsgraph=grafo)
    mesh["generador"] = TAG
    anterior = obj.data
    obj.modifiers.clear()
    obj.data = mesh
    if anterior.users == 0:
        bpy.data.meshes.remove(anterior)
    for cara in mesh.polygons:
        cara.use_smooth = True


def proporcion_infantil(co):
    # La cara se acorta mas que la boveda; no es una escala uniforme del adulto.
    x, y, z = co
    mezcla = suave((z - 60.0) / 55.0)
    sx = mezclar(0.74, 0.84, mezcla)
    sy = mezclar(0.70, 0.83, mezcla)
    altura = z * 0.65 if z <= 90 else 58.5 + (z - 90) * 0.92
    return (x * sx, y * sy, altura)


def transformar_hueso_infantil(obj):
    for vertice in obj.data.vertices:
        vertice.co = Vector(proporcion_infantil(tuple(v / MM for v in vertice.co))) * MM
    obj.data.update()
    return obj


def fusionar_huesos(objetos, nombre, col, material, escena):
    # Una sola superficie cortical: evita piezas transparentes superpuestas.
    escena.view_layers[0].update()
    malla = Malla()
    for obj in objetos:
        desplazamiento = len(malla.vertices)
        for vertice in obj.data.vertices:
            co = obj.matrix_world @ vertice.co
            malla.vertice(tuple(v / MM for v in co), (1, 1, 1, 1))
        for cara in obj.data.polygons:
            malla.cara(tuple(desplazamiento + i for i in cara.vertices))
    unido = objeto_hueso(malla, nombre, col, material)
    print("Fusionando", nombre, "...", flush=True)
    for obj in objetos:
        quitar_temporal(obj)
    voxel = unido.modifiers.new("Fusion_cortical", "REMESH")
    voxel.mode = "VOXEL"
    voxel.voxel_size = {"MEDIA": 0.85, "ALTA": 0.65, "ULTRA": 0.48}[CALIDAD] * MM
    voxel.use_smooth_shade = True
    alisar = unido.modifiers.new("Transiciones_oseas", "SMOOTH")
    alisar.factor = 0.45
    alisar.iterations = 4
    aplicar_modificadores(unido, escena)
    print("Superficie cortical:", len(unido.data.polygons), "caras", flush=True)
    return unido


def volumen_alveolar(diente, forma, col, material):
    # El cuello queda apenas por encima de la cresta osea; la raiz se inserta.
    malla = Malla()
    primero = previo = None
    for k in range(15):
        t = k / 14.0
        z = -0.7 - t * (forma.largo_raiz + 1.8)
        factor = perfil(t, [(0, 1.0), (0.3, 1.08), (0.7, 0.97), (1, 0.78)])
        rx = (forma.ancho * 0.46 + 0.95) * factor
        ry = (forma.fondo * 0.48 + 1.05) * factor
        anillo = []
        for j in range(48):
            a = TAU * j / 48
            local = Vector((rx * math.cos(a), ry * math.sin(a), z)) * MM
            co = diente.matrix_world @ local
            anillo.append(malla.vertice(tuple(v / MM for v in co), (1, 1, 1, 1)))
        if previo is not None:
            malla.unir(previo, anillo)
        else:
            primero = anillo
        previo = anillo
    for anillo, z in ((primero, -0.7), (previo, -forma.largo_raiz - 2.5)):
        centro = diente.matrix_world @ (Vector((0, 0, z)) * MM)
        malla.tapar(anillo, tuple(v / MM for v in centro), (1, 1, 1, 1))
    return objeto_hueso(malla, "Temporal_Proceso_Alveolar", col, material)


def cortador_raices(diente, col):
    mesh = bpy.data.meshes.new("Temporal_Alveolo_Mesh")
    mesh["generador"] = TAG
    caras = [tuple(c.vertices) for c in diente.data.polygons if c.material_index == 1]
    indices = sorted({i for cara in caras for i in cara})
    nuevos = {indice: n for n, indice in enumerate(indices)}
    vertices = []
    for indice in indices:
        co = diente.data.vertices[indice].co.copy()
        co.x *= 1.07
        co.y *= 1.07
        co.z += 0.28 * MM
        vertices.append(diente.matrix_world @ co)
    mesh.from_pydata(vertices, [], [tuple(nuevos[i] for i in c) for c in caras])
    bm = bmesh.new()
    bm.from_mesh(mesh)
    bordes = [arista for arista in bm.edges if arista.is_boundary]
    if bordes:
        bmesh.ops.holes_fill(bm, edges=bordes, sides=0)
    bmesh.ops.recalc_face_normals(bm, faces=list(bm.faces))
    bm.to_mesh(mesh)
    bm.free()
    obj = bpy.data.objects.new("Temporal_Alveolo_" + str(diente["FDI"]), mesh)
    obj["generador"] = TAG
    col.objects.link(obj)
    return obj


def tallar_alveolos(hueso, dientes, escena):
    if not MOSTRAR_RAICES:
        return
    cortadores = coleccion("Temporales_Alveolos", escena)
    print("Tallando", len(dientes), "alveolos en", hueso.name, flush=True)
    for diente, _ in dientes:
        cortador = cortador_raices(diente, cortadores)
        fusion = cortador.modifiers.new("Unir_raices_del_cortador", "REMESH")
        fusion.mode = "VOXEL"
        fusion.voxel_size = 0.20 * MM
        fusion.use_smooth_shade = True
        aplicar_modificadores(cortador, escena)
        simplificar = cortador.modifiers.new("Simplificar_cortador", "DECIMATE")
        simplificar.ratio = 0.055
        aplicar_modificadores(cortador, escena)
        corte = hueso.modifiers.new("Alveolo_" + str(diente["FDI"]), "BOOLEAN")
        corte.operation = "DIFFERENCE"
        corte.solver = "EXACT"
        corte.object = cortador
        # Ambos operandos ya son superficies fusionadas sin intersecciones.
        corte.use_self = False
        aplicar_modificadores(hueso, escena)
        quitar_temporal(cortador)
        diente["insercion"] = "Raices alojadas en alveolos tallados en el hueso"
        print("  Alveolo", diente["FDI"], "terminado", flush=True)
    bpy.data.collections.remove(cortadores)
    hueso["numero_alveolos"] = len(dientes)


def construir_craneo(escena, cabeza, padre_inf, dientes, material, infantil=False):
    grupo = "Infantil" if infantil else "Adulto"
    fijo = coleccion(grupo + "_Craneo_Transparente", escena)
    inferior = coleccion(grupo + "_Mandibula_Transparente", escena)
    partes = []

    def agregar(obj, mandibular=False):
        if infantil:
            transformar_hueso_infantil(obj)
        (mandibulares if mandibular else partes).append(obj)
        return obj

    def cavidad(obj, cortador):
        if infantil:
            transformar_hueso_infantil(cortador)
        restar_volumen(obj, cortador, escena)
        quitar_temporal(cortador)

    cupula = agregar(loft_hueso("Temporal_Boveda", [
        (29, 30, 43, 31), (45, 44, 64, 26), (70, 61, 78, 24),
        (101, 68, 82, 23), (122, 61, 72, 21), (140, 44, 52, 19),
        (151, 23, 27, 19), (156, 0.5, 0.5, 19)], fijo, material, 112, 96, casquete=True))
    cavidad(cupula, elipsoide_hueso("Temporal_Interior", (0, 25, 94),
                                   (60.5, 74, 55), fijo, material))
    cavidad(cupula, elipsoide_hueso("Temporal_Retiro_Facial", (0, -70, 43),
                                   (62, 63, 53), fijo, material))
    agregar(loft_hueso("Temporal_Maxilar", [
        (12, 30.5, 33.5, 0), (25, 33, 35, -1), (39, 38, 34, -3),
        (54, 43, 33, -6), (66, 45, 31, -5)], fijo, material))
    agregar(elipsoide_hueso("Temporal_Frontal", (0, -44, 91),
                            (46, 13, 13), fijo, material))
    agregar(loft_hueso("Temporal_Puente_Nasal", [
        (61, 4, 4, -49), (73, 6, 5, -52), (85, 10, 6, -51),
        (93, 12, 6, -47)], fijo, material, 56, 36))
    for lado in (-1, 1):
        agregar(elipsoide_hueso("Temporal_Pomulo", (lado * 46, -27, 57),
                                (13, 14, 12), fijo, material))
        puntos = [(11, -49, 73, 4, 4), (16, -49, 84, 4.5, 4),
                  (31, -46, 88, 5, 5), (47, -37, 81, 5.5, 5),
                  (51, -33, 69, 5, 5), (43, -41, 58, 5, 4.5),
                  (28, -46, 56, 4.5, 4), (16, -49, 62, 4, 4)]
        agregar(tubo_hueso("Temporal_Reborde_Orbital",
                          [(x * lado, y, z, a, b) for x, y, z, a, b in puntos],
                          fijo, material, cerrada=True, pasos=10, segmentos=24))
        arco = [(lado * 45, -35, 57, 5, 5), (lado * 58, -20, 52, 4, 4),
                (lado * 62, 0, 54, 3.3, 3.5), (lado * 57, 20, 59, 4, 4.5),
                (lado * 47, 32, 63, 6, 6)]
        agregar(tubo_hueso("Temporal_Arco_Cigomatico", arco, fijo, material))
        agregar(elipsoide_hueso("Temporal_Mastoides", (lado * 46, 44, 37),
                                (5, 7, 11), fijo, material))
        agregar(elipsoide_hueso("Temporal_Fosa_Articular", (lado * 42, 28, 36),
                                (9, 6, 4), fijo, material))
    escena.view_layers[0].update()
    for diente, forma in dientes[True]:
        partes.append(volumen_alveolar(diente, forma, fijo, material))
    craneo = fusionar_huesos(partes, grupo + "_Craneo_Maxilar", fijo, material, escena)
    cavidad(craneo, elipsoide_hueso("Temporal_Paladar", (0, 1, 13),
                                   (23, 26, 18), fijo, material))
    for lado in (-1, 1):
        cavidad(craneo, cavidad_orbitaria("Temporal_Orbita", lado, fijo, material))
        cavidad(craneo, elipsoide_hueso("Temporal_Foramen_Infraorbitario",
                                       (lado * 31, -38, 45), (1.4, 12, 1.2), fijo, material, segmentos=32, niveles=20))
    cavidad(craneo, loft_hueso("Temporal_Nariz", [
        (29, 2, 29, -44), (34, 9, 30, -44), (43, 11.5, 31, -44),
        (51, 8.5, 31, -45), (61, 4.5, 30, -45), (65, 1.5, 28, -45)],
        fijo, material, 64, 48))
    cavidad(craneo, elipsoide_hueso("Temporal_Foramen_Magno", (0, 34, 23),
                                   (13, 17, 21), fijo, material))
    tallar_alveolos(craneo, dientes[True], escena)
    craneo.parent = cabeza
    craneo["grupo"] = grupo

    material = material_craneo(mandibula=True)
    mandibulares = []
    cuerpo = [(-31, 22, -21, 6.5, 13), (-30, 1, -23, 6.5, 12),
              (-25, -17, -24, 6.2, 12), (-14, -29, -25, 5.7, 12),
              (0, -34, -25, 6.3, 13), (14, -29, -25, 5.7, 12),
              (25, -17, -24, 6.2, 12), (30, 1, -23, 6.5, 12),
              (31, 22, -21, 6.5, 13)]
    agregar(tubo_hueso("Temporal_Cuerpo_Mandibular", cuerpo, inferior, material,
                       pasos=14, segmentos=36), True)
    for lado in (-1, 1):
        rama = [(lado * 31, 21, -24, 6, 12), (lado * 34, 23, -9, 5.2, 13),
                (lado * 38, 24, 10, 4.7, 11), (lado * 41, 27, 25, 3.8, 7),
                (lado * 42, 28, 31, 3.5, 4)]
        agregar(tubo_hueso("Temporal_Rama", rama, inferior, material,
                           pasos=12, segmentos=28), True)
        agregar(elipsoide_hueso("Temporal_Condilo", (lado * 42, 28, 31),
                                (7, 4, 4), inferior, material, segmentos=48, niveles=32), True)
        coronoides = [(lado * 35, 12, 7, 4, 4), (lado * 38, 6, 24, 3, 3.6),
                     (lado * 39, 4, 35, 0.9, 1.2)]
        agregar(tubo_hueso("Temporal_Coronoides", coronoides, inferior, material), True)
    agregar(elipsoide_hueso("Temporal_Menton", (0, -37, -30),
                            (10, 3.5, 9), inferior, material, segmentos=48, niveles=32), True)
    for diente, forma in dientes[False]:
        mandibulares.append(volumen_alveolar(diente, forma, inferior, material))
    mandibula = fusionar_huesos(mandibulares, grupo + "_Mandibula", inferior, material, escena)
    for lado in (-1, 1):
        cavidad(mandibula, elipsoide_hueso("Temporal_Foramen_Mentoniano",
                                          (lado * 23, -27, -24), (1.3, 12, 1.1),
                                          inferior, material, segmentos=32, niveles=20))
    tallar_alveolos(mandibula, dientes[False], escena)
    mandibula.parent = padre_inf
    mandibula.location = -padre_inf.location
    mandibula["grupo"] = grupo
    print("Huesos y alveolos terminados:", grupo, flush=True)
    return craneo, mandibula


def animar_mandibula(escena, padre_inf):
    escena.frame_start = 1
    escena.frame_end = 120
    escena.render.fps = 30
    if ANIMAR_APERTURA and DISTRIBUCION == "ARCADAS":
        for fotograma, grados in ((1, 0), (40, APERTURA_INFERIOR_GRADOS),
                                   (70, APERTURA_INFERIOR_GRADOS), (110, 0), (120, 0)):
            padre_inf.rotation_euler.x = math.radians(grados)
            padre_inf.keyframe_insert(data_path="rotation_euler", index=0, frame=fotograma)
        padre_inf.animation_data.action["generador"] = TAG
        escena.frame_set(40)
    else:
        padre_inf.rotation_euler.x = math.radians(APERTURA_INFERIOR_GRADOS) if DISTRIBUCION == "ARCADAS" else 0.0
        escena.frame_set(1)


def preparar_escena():
    candidata = bpy.data.scenes.get(NOMBRE_ESCENA)
    escena = candidata if candidata and candidata.get("generador") == TAG else bpy.data.scenes.new(NOMBRE_ESCENA)
    escena["generador"] = TAG
    escena["descripcion"] = "Adulto a la izquierda (32 permanentes), infantil a la derecha (20 temporales); aproximacion artistica"
    # Solo se reemplaza lo creado por este generador al ejecutarlo de nuevo.
    for obj in list(escena.objects):
        if obj.get("generador") == TAG:
            bpy.data.objects.remove(obj, do_unlink=True)
    for col in list(bpy.data.collections):
        if col.get("generador") == TAG and col.users == 0:
            bpy.data.collections.remove(col)
    for col in list(escena.collection.children):
        if col.get("generador") == TAG:
            escena.collection.children.unlink(col)
            if col.users == 0:
                bpy.data.collections.remove(col)
    for banco in (bpy.data.meshes, bpy.data.curves, bpy.data.materials, bpy.data.cameras, bpy.data.lights, bpy.data.actions):
        for dato in list(banco):
            if dato.get("generador") == TAG and dato.users == 0:
                banco.remove(dato)
    escena.unit_settings.system = "METRIC"
    escena.unit_settings.scale_length = 1.0
    escena.unit_settings.length_unit = "MILLIMETERS"
    if bpy.context.window is not None:
        bpy.context.window.scene = escena
    return escena


def coleccion(nombre, escena):
    col = bpy.data.collections.new(nombre)
    col["generador"] = TAG
    escena.collection.children.link(col)
    return col


def control(nombre, col):
    obj = bpy.data.objects.new(nombre, None)
    obj["generador"] = TAG
    obj.empty_display_type = "PLAIN_AXES"
    obj.empty_display_size = 4.0 * MM
    col.objects.link(obj)
    return obj


def tabla_arco(rx, ry):
    tabla = [(0.0, 0.0)]
    anterior = Vector((0.0, -ry))
    longitud = 0.0
    for i in range(1, 2001):
        theta = math.radians(145.0) * i / 2000
        punto = Vector((rx * math.sin(theta), -ry * math.cos(theta)))
        longitud += (punto - anterior).length
        tabla.append((longitud, theta))
        anterior = punto
    return tabla


def angulo_arco(longitud, tabla):
    for (la, ta), (lb, tb) in zip(tabla, tabla[1:]):
        if longitud <= lb:
            return mezclar(ta, tb, (longitud - la) / (lb - la))
    raise ValueError("La arcada es demasiado corta para el espacio solicitado.")


def situar_diente(obj, forma, lado, theta, rx, ry, padre, x_atlas=0.0):
    z = (SEPARACION_ARCADAS_MM / 2.0 + forma.alto) * (1 if forma.superior else -1)
    if DISTRIBUCION == "ATLAS":
        obj.location = Vector((x_atlas, 0, z)) * MM
        # Ambos lados muestran la superficie vestibular a la camara.
        tangente = Vector((lado, 0, 0))
        fuera = Vector((0, -1, 0))
    else:
        x = lado * rx * math.sin(theta)
        y = -ry * math.cos(theta) + (1.6 if not forma.superior else 0.0)
        obj.location = Vector((x, y, z)) * MM - padre.location
        tangente = Vector((lado * rx * math.cos(theta), ry * math.sin(theta), 0)).normalized()
        fuera = Vector((lado * ry * math.sin(theta), -rx * math.cos(theta), 0)).normalized()
    # Bases con determinante positivo: reflejamos la malla cuando hace falta.
    vertical = Vector((0, 0, -1 if forma.superior else 1))
    signo = 1.0 if tangente.cross(fuera).dot(vertical) > 0 else -1.0
    eje_x = tangente * signo
    rotacion = Matrix((eje_x, fuera, vertical)).transposed()
    if signo < 0:
        for v in obj.data.vertices:
            v.co.x *= -1.0
        bm = bmesh.new()
        bm.from_mesh(obj.data)
        bmesh.ops.recalc_face_normals(bm, faces=list(bm.faces))
        bm.to_mesh(obj.data)
        bm.free()
    obj.parent = padre
    obj.rotation_mode = "QUATERNION"
    obj.rotation_quaternion = rotacion.to_quaternion()


def orientar(obj, destino):
    obj.rotation_euler = (Vector(destino) * MM - obj.location).to_track_quat("-Z", "Y").to_euler()


def iluminar(escena, col, atlas=False):
    luces = [
        ("Principal", (-85, -155, 160), 0.43, 125, (1.0, 0.95, 0.87)),
        ("Relleno", (90, -85, 55), 0.23, 110, (0.88, 0.94, 1.0)),
        ("Contorno", (5, 110, 145), 0.54, 105, (1.0, 1.0, 1.0)),
        ("Inferior", (-25, -100, -80), 0.12, 85, (1.0, 0.97, 0.92)),
    ]
    for grupo, desplazamiento in (("Adulto", -SEPARACION_CRANEOS_MM / 2),
                                  ("Infantil", SEPARACION_CRANEOS_MM / 2)):
        for nombre, posicion, energia, tamano, color in luces:
            datos = bpy.data.lights.new(grupo + "_" + nombre, "AREA")
            datos["generador"] = TAG
            datos.energy = energia
            datos.shape = "DISK"
            datos.size = tamano * MM
            datos.color = color
            obj = bpy.data.objects.new(datos.name, datos)
            obj["generador"] = TAG
            col.objects.link(obj)
            obj.location = Vector((posicion[0] + desplazamiento, posicion[1], posicion[2])) * MM
            orientar(obj, (desplazamiento, -8, 50))
    if escena.world is None or escena.world.get("generador") != TAG:
        escena.world = bpy.data.worlds.new("Mundo_Dental")
        escena.world["generador"] = TAG
    escena.world.use_nodes = True
    n, l = escena.world.node_tree.nodes, escena.world.node_tree.links
    n.clear()
    salida = n.new("ShaderNodeOutputWorld")
    ambiente = n.new("ShaderNodeBackground")
    ambiente.inputs["Color"].default_value = (0.62, 0.68, 0.75, 1)
    ambiente.inputs["Strength"].default_value = 0.24
    fondo = n.new("ShaderNodeBackground")
    fondo.inputs["Color"].default_value = (0.037, 0.055, 0.075, 1)
    fondo.inputs["Strength"].default_value = 0.65
    rayos = n.new("ShaderNodeLightPath")
    mezcla = n.new("ShaderNodeMixShader")
    l.new(rayos.outputs["Is Camera Ray"], mezcla.inputs[0])
    l.new(ambiente.outputs[0], mezcla.inputs[1])
    l.new(fondo.outputs[0], mezcla.inputs[2])
    l.new(mezcla.outputs[0], salida.inputs["Surface"])


def configurar_camara(escena, col):
    datos = bpy.data.cameras.new("Camara_Dental")
    datos["generador"] = TAG
    datos.type = "ORTHO"
    datos.clip_start = 0.001
    datos.clip_end = 10.0
    obj = bpy.data.objects.new("Camara_Dental", datos)
    obj["generador"] = TAG
    col.objects.link(obj)
    obj.location = Vector((0, -600, 88 if INCLUIR_CRANEO else 40)) * MM
    orientar(obj, (0, 0, 50 if INCLUIR_CRANEO else -5))
    datos.ortho_scale = max(400, SEPARACION_CRANEOS_MM + 175) * MM
    escena.camera = obj
    return obj


def configurar_render(escena):
    escena.render.engine = "CYCLES"
    escena.cycles.samples = MUESTRAS_RENDER
    escena.cycles.use_denoising = True
    escena.cycles.max_bounces = 8
    escena.cycles.diffuse_bounces = 4
    escena.cycles.glossy_bounces = 4
    escena.cycles.transmission_bounces = 4
    escena.cycles.transparent_max_bounces = 48 if INCLUIR_CRANEO else 8
    escena.render.resolution_x = RESOLUCION_X
    escena.render.resolution_y = RESOLUCION_Y
    escena.render.resolution_percentage = 100
    escena.render.film_transparent = FONDO_TRANSPARENTE
    escena.render.image_settings.file_format = "PNG"
    escena.render.image_settings.color_mode = "RGBA"
    escena.render.filepath = "//adulto_infantil_render.png"
    for transformacion in ("AgX", "Filmic", "Standard"):
        try:
            escena.view_settings.view_transform = transformacion
            break
        except TypeError:
            pass
    escena.view_settings.exposure = -0.35


def configurar_vista(escena, camara):
    if bpy.context.window is None:
        return
    escena.view_layers[0].update()
    for area in bpy.context.window.screen.areas:
        if area.type == "VIEW_3D":
            espacio = area.spaces.active
            espacio.clip_start = 0.001
            espacio.clip_end = 10.0
            espacio.shading.type = "MATERIAL"
            espacio.shading.use_scene_world = False
            espacio.shading.use_scene_lights = False
            espacio.overlay.show_floor = False
            espacio.overlay.show_axis_x = False
            espacio.overlay.show_axis_y = False
            espacio.region_3d.view_perspective = "CAMERA"


def rotulo(nombre, x, col):
    fuente = bpy.data.curves.new(nombre + "_Texto", "FONT")
    fuente["generador"] = TAG
    fuente.body = nombre.upper()
    fuente.align_x = "CENTER"
    fuente.size = 7.5 * MM
    fuente.extrude = 0
    obj = bpy.data.objects.new(nombre + "_Rotulo", fuente)
    obj["generador"] = TAG
    col.objects.link(obj)
    obj.location = Vector((x, -38, -64)) * MM
    obj.rotation_euler.x = math.pi / 2
    mat = bpy.data.materials.get("Rotulos_Dentales")
    if mat is None or mat.get("generador") != TAG:
        mat = bpy.data.materials.new("Rotulos_Dentales")
        mat["generador"] = TAG
        mat.use_nodes = True
        shader = mat.node_tree.nodes.get("Principled BSDF")
        entrada(shader, ["Base Color"], (0.75, 0.83, 0.89, 1))
        entrada(shader, ["Emission Color", "Emission"], (0.75, 0.83, 0.89, 1))
        entrada(shader, ["Emission Strength"], 0.8)
        entrada(shader, ["Roughness"], 0.7)
    fuente.materials.append(mat)


def generar_denticion(escena, cabeza, esmalte, cemento, infantil=False):
    grupo = "Infantil" if infantil else "Adulto"
    print("Modelando denticion:", grupo, flush=True)
    sup = coleccion(grupo + "_Dientes_Superiores", escena)
    inf = coleccion(grupo + "_Dientes_Inferiores", escena)
    padre_sup = control(grupo + "_Arcada_Superior", sup)
    padre_inf = control(grupo + "_Arcada_Inferior", inf)
    padre_sup.parent = cabeza
    padre_inf.parent = cabeza
    pivote = proporcion_infantil((0, 28, 31)) if infantil else (0, 28, 31)
    padre_inf.location = Vector(pivote) * MM
    padre_inf["apertura_grados"] = APERTURA_INFERIOR_GRADOS
    n, niveles, radiales, niveles_raiz, n_raiz = RES[CALIDAD]
    dimensiones = DIMENSIONES_TEMPORALES if infantil else DIMENSIONES
    nombres = NOMBRES_TEMPORALES if infantil else NOMBRES
    maximo = 5 if infantil else 8 if INCLUIR_MUELAS_DEL_JUICIO else 7
    dientes = {True: [], False: []}
    for superior, col, padre in ((True, sup, padre_sup), (False, inf, padre_inf)):
        if infantil:
            rx, ry = (21.5, 25.0) if superior else (20.0, 24.0)
        else:
            rx, ry = (29.0, 36.0) if superior else (27.0, 35.5)
        tabla = tabla_arco(rx, ry)
        for lado in (-1, 1):
            longitud = ESPACIO_ENTRE_DIENTES_MM / 2
            for posicion in range(1, maximo + 1):
                tipo = "incisivo" if posicion <= 2 else "canino" if posicion == 3 else "molar" if infantil or posicion >= 6 else "premolar"
                forma = Forma(tipo, *dimensiones[superior][posicion - 1], superior, posicion, infantil)
                centro = longitud + forma.ancho / 2
                theta = angulo_arco(centro, tabla)
                cuadrante = (1 if lado == -1 else 2) if superior else (4 if lado == -1 else 3)
                if infantil:
                    cuadrante += 4
                fdi = 10 * cuadrante + posicion
                nombre = grupo + "_Diente_{}_{}".format(fdi, nombres[posicion - 1])
                malla = Malla()
                variacion = 0.006 * math.sin(fdi * 2.17)
                cervical = construir_corona(malla, forma, n, niveles, radiales, variacion)
                varias = tipo == "molar" or (not infantil and superior and posicion == 4)
                num_raices = 3 if superior and tipo == "molar" else 2 if varias else 1
                if MOSTRAR_RAICES:
                    if varias:
                        num_raices = raices_multiples(malla, forma, cervical, niveles_raiz, n_raiz)
                    else:
                        raiz_unica(malla, forma, cervical, niveles_raiz)
                else:
                    malla.tapar(cervical, (0, 0, 0), color_esmalte(0))
                obj = malla.objeto(nombre, col, [esmalte, cemento])
                obj["FDI"] = fdi
                obj["tipo"] = nombres[posicion - 1]
                obj["grupo"] = grupo
                obj["denticion"] = "Temporal" if infantil else "Permanente"
                obj["arcada"] = "Superior" if superior else "Inferior"
                obj["numero_raices_modeladas"] = num_raices if MOSTRAR_RAICES else 0
                obj["perfil_raiz"] = "Cuerpo ancho, curvatura suave y apice redondeado"
                obj["uso"] = "Visualizacion artistica; no clinico"
                situar_diente(obj, forma, lado, theta, rx, ry, padre, lado * centro)
                dientes[superior].append((obj, forma))
                longitud += forma.ancho + ESPACIO_ENTRE_DIENTES_MM
    return dientes, padre_inf


def main():
    if CALIDAD not in RES:
        raise ValueError("CALIDAD debe ser MEDIA, ALTA o ULTRA.")
    if DISTRIBUCION not in ("ARCADAS", "ATLAS"):
        raise ValueError("DISTRIBUCION debe ser ARCADAS o ATLAS.")
    if INCLUIR_CRANEO and DISTRIBUCION != "ARCADAS":
        raise ValueError("El craneo requiere DISTRIBUCION = ARCADAS. Para usar ATLAS, desactiva INCLUIR_CRANEO.")
    if not 0.0 <= TRANSPARENCIA_CRANEO <= 1.0:
        raise ValueError("TRANSPARENCIA_CRANEO debe estar entre 0 y 1.")
    if ESPACIO_ENTRE_DIENTES_MM < 0 or SEPARACION_ARCADAS_MM < 0:
        raise ValueError("Las separaciones no pueden ser negativas.")
    escena = preparar_escena()
    estudio = coleccion("Camara_y_Luces", escena)
    escena.frame_set(1)
    esmalte = material_dental("Esmalte_marfil")
    cemento = material_dental("Cemento_radicular", raiz=True)
    hueso = material_craneo() if INCLUIR_CRANEO else None
    total = 0
    pivotes = []
    for infantil in (False, True):
        grupo = "Infantil" if infantil else "Adulto"
        cabeza = control(grupo + "_Cabeza", estudio)
        cabeza["grupo"] = grupo
        dientes, padre_inf = generar_denticion(escena, cabeza, esmalte, cemento, infantil)
        total += sum(len(lista) for lista in dientes.values())
        if INCLUIR_CRANEO:
            construir_craneo(escena, cabeza, padre_inf, dientes, hueso, infantil)
        x = SEPARACION_CRANEOS_MM / 2 * (1 if infantil else -1)
        cabeza.location.x = x * MM
        cabeza.rotation_euler.z = math.radians(-18 if infantil else 18)
        if MOSTRAR_ROTULOS:
            rotulo(grupo, x, estudio)
        pivotes.append(padre_inf)
    for pivote in pivotes:
        animar_mandibula(escena, pivote)
    configurar_render(escena)
    iluminar(escena, estudio, DISTRIBUCION == "ATLAS")
    camara = configurar_camara(escena, estudio)
    configurar_vista(escena, camara)
    print("LISTO: {} dientes separados en la escena '{}'.".format(total, escena.name))
    print("F12: render. Cada Diente_FDI es una pieza editable.")
    print("Adulto_Cabeza: izquierda. Infantil_Cabeza: derecha.")
    if INCLUIR_CRANEO:
        print("Craneo transparente incluido. Espacio: reproducir apertura y cierre.")
    return escena


if __name__ == "__main__":
    main()

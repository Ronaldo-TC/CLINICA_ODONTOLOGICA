import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

const previewCanvas = document.getElementById('admision-odontograma-canvas');

if (previewCanvas) {
    const status = document.getElementById('admision-odontograma-status');
    const preview = previewCanvas.closest('.admision-odontograma-preview');
    const previewHome = preview?.parentElement;
    const previewNext = preview?.nextSibling;
    const modal = document.getElementById('modalOdontograma3D');
    const modalStage = document.getElementById('odontograma-3d-modal-stage');
    const openButton = document.getElementById('abrir-odontograma-3d');
    const closeButton = document.getElementById('cerrar-odontograma-3d');

    if (modal) document.body.append(modal);

    const closeModal = () => {
        if (!modal || !preview) return;

        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        if (previewHome) {
            previewHome.insertBefore(preview, previewNext?.parentNode === previewHome ? previewNext : null);
        }
        if (openButton?.isConnected) openButton.focus();
    };

    openButton?.addEventListener('click', () => {
        if (!modal || !modalStage || !preview) return;

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        modalStage.append(preview);
        closeButton?.focus();
    });

    closeButton?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal?.classList.contains('open')) closeModal();
    });

    try {
        const width = previewCanvas.clientWidth;
        const height = previewCanvas.clientHeight;
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(35, width / height, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({
            canvas: previewCanvas,
            alpha: true,
            antialias: true,
            powerPreference: 'low-power',
        });

        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
        renderer.setSize(width, height, false);
        renderer.outputColorSpace = THREE.SRGBColorSpace;
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.15;

        scene.add(new THREE.HemisphereLight(0xffffff, 0x647080, 2));

        const keyLight = new THREE.DirectionalLight(0xffffff, 3);
        keyLight.position.set(4, 7, 8);
        scene.add(keyLight);

        const fillLight = new THREE.DirectionalLight(0xb5d8ff, 1.5);
        fillLight.position.set(-6, 2, -4);
        scene.add(fillLight);

        const controls = new OrbitControls(camera, previewCanvas);
        controls.enableDamping = true;
        controls.dampingFactor = 0.06;
        controls.enablePan = false;
        controls.autoRotate = true;
        controls.autoRotateSpeed = 1.2;

        const loader = new GLTFLoader();
        loader.load(
            previewCanvas.dataset.modelUrl,
            (gltf) => {
                const model = gltf.scene;
                const bounds = new THREE.Box3().setFromObject(model);
                const size = bounds.getSize(new THREE.Vector3());
                const center = bounds.getCenter(new THREE.Vector3());
                const largestDimension = Math.max(size.x, size.y, size.z);

                if (!Number.isFinite(largestDimension) || largestDimension <= 0) {
                    const error = new Error('El archivo GLB no contiene un modelo 3D visible.');
                    console.error(error.message);
                    if (status) status.textContent = 'El archivo 3D no contiene un modelo visible.';
                    return;
                }

                model.position.sub(center);
                model.scale.setScalar(5 / largestDimension);
                scene.add(model);

                controls.target.set(0, 0, 0);
                const distance = (5 / (2 * Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)))) * 1.35;
                camera.position.set(distance * 0.72, distance * 0.55, distance);
                camera.near = Math.max(distance / 100, 0.01);
                camera.far = distance * 10;
                camera.updateProjectionMatrix();
                controls.update();

                if (status) status.textContent = 'Arrastra para girar · rueda para acercar';
            },
            (progress) => {
                if (status && progress.total > 0) {
                    const percentage = Math.round((progress.loaded / progress.total) * 100);
                    status.textContent = `Cargando odontograma 3D... ${percentage}%`;
                }
            },
            (error) => {
                console.error('No se pudo cargar el modelo del odontograma.', error);
                if (status) status.textContent = 'No se pudo cargar el modelo 3D.';
            },
        );

        const resizeObserver = new ResizeObserver(() => {
            const nextWidth = previewCanvas.clientWidth;
            const nextHeight = previewCanvas.clientHeight;
            if (!nextWidth || !nextHeight) return;

            camera.aspect = nextWidth / nextHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(nextWidth, nextHeight, false);
        });
        resizeObserver.observe(previewCanvas);

        const animate = () => {
            controls.update();
            renderer.render(scene, camera);
            requestAnimationFrame(animate);
        };
        animate();
    } catch (error) {
        console.error('No se pudo iniciar la vista previa del odontograma 3D.', error);
        if (status) status.textContent = 'La vista 3D no está disponible en este navegador.';
    }
} else {
    import('./odontograma/index.js');
}
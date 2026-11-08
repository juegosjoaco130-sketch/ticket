const API_URL = 'http://localhost:8000/api';

const formulario = document.getElementById('formulario-usuario');
const campoCedula = document.getElementById('cedula');
const campoUsuario = document.getElementById('usuario');
const campoPassword = document.getElementById('password');
const campoRol = document.getElementById('rol');
const mensajeError = document.getElementById('mensaje-error');
const mensajeExito = document.getElementById('mensaje-exito');
const botonGuardar = formulario.querySelector('button[type="submit"]');

verificarPermisos();

formulario.addEventListener('submit', async (eventoEnvio) => {
    eventoEnvio.preventDefault();

    ocultarMensajes();

    const cedula = campoCedula.value.trim();
    const usuario = campoUsuario.value.trim();
    const password = campoPassword.value;
    const rol = campoRol.value;

    if (
        cedula === '' ||
        usuario === '' ||
        password === '' ||
        rol === ''
    ) {
        mostrarError('Todos los campos son obligatorios.');
        return;
    }

    bloquearFormulario(true);

    try {
        const respuesta = await fetch(`${API_URL}/usuarios`, {
            method: 'POST',

            headers: {
                'Content-Type': 'application/json'
            },

            credentials: 'include',

            body: JSON.stringify({
                cedula: cedula,
                usuario: usuario,
                password: password,
                rol: rol
            })
        });

        const datos = await respuesta.json();

        if (!respuesta.ok) {
            throw new Error(
                datos.error || 'No se pudo crear el usuario.'
            );
        }

        mensajeExito.textContent =
            'Usuario creado correctamente.';

        mensajeExito.hidden = false;

        formulario.reset();
        campoCedula.focus();
    } catch (error) {
        if (error instanceof TypeError) {
            mostrarError(
                'No fue posible comunicarse con el servidor.'
            );
        } else {
            mostrarError(error.message);
        }
    } finally {
        bloquearFormulario(false);
    }
});

async function verificarPermisos() {
    try {
        const respuesta = await fetch(`${API_URL}/sesion`, {
            method: 'GET',
            credentials: 'include'
        });

        if (!respuesta.ok) {
            window.location.replace('../index.html');
            return;
        }

        const datos = await respuesta.json();

        if (datos.usuario.rol !== 'encargado') {
            window.location.replace('../index.html');
        }
    } catch (error) {
        window.location.replace('../index.html');
    }
}

function mostrarError(mensaje) {
    mensajeError.textContent = mensaje;
    mensajeError.hidden = false;
}

function ocultarMensajes() {
    mensajeError.textContent = '';
    mensajeError.hidden = true;

    mensajeExito.textContent = '';
    mensajeExito.hidden = true;
}

function bloquearFormulario(bloquear) {
    campoCedula.disabled = bloquear;
    campoUsuario.disabled = bloquear;
    campoPassword.disabled = bloquear;
    campoRol.disabled = bloquear;
    botonGuardar.disabled = bloquear;

    if (bloquear === true) {
        botonGuardar.textContent = 'Guardando...';
    } else {
        botonGuardar.textContent = 'Crear usuario';
    }
}
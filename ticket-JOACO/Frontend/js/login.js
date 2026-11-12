const API_URL = 'http://localhost:8000/api';

const formulario = document.getElementById('formulario-login');
const campoUsuario = document.getElementById('usuario');
const campoPassword = document.getElementById('password');
const mensajeError = document.getElementById('mensaje-error');
const botonIngresar = formulario.querySelector('button[type="submit"]');

formulario.addEventListener('submit', async (eventoEnvio) => {
    eventoEnvio.preventDefault(); //evito que se recargue la pagina al enviar el formulario

    ocultarError(); //oculto errores de intentos anteriores

    const usuario = campoUsuario.value.trim(); //elimino espacios en blanco al inicio y al final del usuario
    const password = campoPassword.value;

    //La contraseña se envía tal cual fue escrita; solo se usa trim()
    //para detectar el caso de un campo con únicamente espacios.
    const passwordVacia = password.trim() === '';

    if (usuario === '' || passwordVacia) {
        if (usuario === '' && passwordVacia) {
            mostrarError('Debes completar el usuario y la contraseña.');
            campoUsuario.focus();
        } else if (usuario === '') {
            mostrarError('Debes completar el usuario.');
            campoUsuario.focus();
        } else {
            mostrarError('Debes completar la contraseña.');
            campoPassword.focus();
        }

        return;
    }

    bloquearFormulario(true);

    try {
        const respuesta = await fetch(API_URL + '/login', {
            method: 'POST',

            headers: {
                'Content-Type': 'application/json'
            },

            credentials: 'include',

            body: JSON.stringify({
                usuario: usuario,
                password: password
            })
        });

        //Si el servidor devuelve algo que no es JSON (por ejemplo una
        //página de error), se informa con un mensaje claro en lugar de
        //mostrar un error técnico al usuario.
        let datos;

        try {
            datos = await respuesta.json();
        } catch (errorParseo) {
            throw new Error(
                'Respuesta inválida del servidor. Intenta nuevamente.'
            );
        }

        if (!respuesta.ok) {
            //Ante credenciales incorrectas se limpia la contraseña y se
            //devuelve el foco al campo para facilitar un nuevo intento.
            if (respuesta.status === 401) {
                campoPassword.value = '';
                campoPassword.focus();
            }

            throw new Error(
                datos.error || 'No fue posible iniciar sesión.'
            );
        }

        const rol = datos.usuario?.rol;

        if (rol === 'repartidor') {
            window.location.replace('templates/repartidor.html');
            return;
        }

        if (rol === 'encargado') {
            window.location.replace('templates/encargado.html');
            return;
        }

        throw new Error('El usuario no tiene un rol válido.');
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

function mostrarError(mensaje) {
    mensajeError.textContent = mensaje;
    mensajeError.hidden = false;
}

function ocultarError() {
    mensajeError.textContent = '';
    mensajeError.hidden = true;
}

function bloquearFormulario(bloquear) {
    campoUsuario.disabled = bloquear;
    campoPassword.disabled = bloquear;
    botonIngresar.disabled = bloquear;

     if (bloquear === true) {
        botonIngresar.textContent = 'Ingresando...';
        formulario.setAttribute('aria-busy', 'true');
    } else {
        botonIngresar.textContent = 'Iniciar sesión';
        formulario.setAttribute('aria-busy', 'false');
    }
}

const API_URL = 'http://localhost:8000/api';

const formulario = document.getElementById('formulario-ticket');
const campoIdTicket = document.getElementById('id-ticket');
const campoDescripcion = document.getElementById('descripcion');
const campoSala = document.getElementById('sala');
const campoPrioridad = document.getElementById('prioridad');

const tituloFormulario = document.getElementById(
    'titulo-formulario'
);

const listaTickets = document.getElementById('lista-tickets');
const sinTickets = document.getElementById('sin-tickets');

const mensajeError = document.getElementById('mensaje-error');
const mensajeExito = document.getElementById('mensaje-exito');

const botonGuardar = formulario.querySelector(
    'button[type="submit"]'
);

const botonCancelar = document.getElementById(
    'boton-cancelar'
);

const botonLogout = document.getElementById(
    'boton-logout'
);

/*
|--------------------------------------------------------------------------
| Inicio
|--------------------------------------------------------------------------
*/

cargarDatos();

/*
|--------------------------------------------------------------------------
| Crear o modificar un ticket
|--------------------------------------------------------------------------
*/

formulario.addEventListener(
    'submit',
    async function (eventoEnvio) {
        eventoEnvio.preventDefault();

        ocultarMensajes();

        const idTicket = campoIdTicket.value;
        const descripcion = campoDescripcion.value.trim();
        const sala = campoSala.value;
        const idPrioridad = campoPrioridad.value;

        if (descripcion === '' || idPrioridad === '') {
            mostrarError(
                'Debes completar la descripción y la prioridad.'
            );

            return;
        }

        bloquearFormulario(true);

        const datosTicket = {
            descripcion: descripcion,
            sala: sala,
            id_prioridad: Number(idPrioridad)
        };

        let metodo = 'POST';

        if (idTicket !== '') {
            metodo = 'PUT';
            datosTicket.id_ticket = Number(idTicket);
        }

        try {
            const respuesta = await fetch(
                API_URL + '/tickets',
                {
                    method: metodo,

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    credentials: 'include',

                    body: JSON.stringify(datosTicket)
                }
            );

            const datos = await respuesta.json();

            if (!respuesta.ok) {
                throw new Error(
                    datos.error ||
                    'No fue posible guardar el ticket.'
                );
            }

            mensajeExito.textContent = datos.mensaje;
            mensajeExito.hidden = false;

            volverModoCreacion();

            await cargarDatos();
        } catch (error) {
            mostrarError(error.message);
        } finally {
            bloquearFormulario(false);
        }
    }
);

/*
|--------------------------------------------------------------------------
| Cancelar modificación
|--------------------------------------------------------------------------
*/

botonCancelar.addEventListener('click', function () {
    volverModoCreacion();
    ocultarMensajes();
});

/*
|--------------------------------------------------------------------------
| Cerrar sesión
|--------------------------------------------------------------------------
*/

botonLogout.addEventListener('click', async function () {
    try {
        await fetch(
            API_URL + '/logout',
            {
                method: 'POST',
                credentials: 'include'
            }
        );
    } finally {
        window.location.replace('../index.html');
    }
});

/*
|--------------------------------------------------------------------------
| Obtener tickets y prioridades
|--------------------------------------------------------------------------
*/

async function cargarDatos() {
    try {
        const respuesta = await fetch(
            API_URL + '/tickets',
            {
                method: 'GET',
                credentials: 'include'
            }
        );

        const datos = await respuesta.json();

        if (
            respuesta.status === 401 ||
            respuesta.status === 403
        ) {
            window.location.replace('../index.html');
            return;
        }

        if (!respuesta.ok) {
            throw new Error(
                datos.error ||
                'No fue posible obtener los tickets.'
            );
        }

        cargarPrioridades(datos.prioridades);
        mostrarTickets(datos.tickets);
    } catch (error) {
        mostrarError(error.message);
    }
}

/*
|--------------------------------------------------------------------------
| Mostrar prioridades
|--------------------------------------------------------------------------
*/

function cargarPrioridades(prioridades) {
    const prioridadSeleccionada = campoPrioridad.value;

    campoPrioridad.innerHTML = '';

    const primeraOpcion = document.createElement('option');

    primeraOpcion.value = '';
    primeraOpcion.textContent = 'Selecciona una prioridad';

    campoPrioridad.appendChild(primeraOpcion);

    prioridades.forEach(function (prioridad) {
        const opcion = document.createElement('option');

        opcion.value = prioridad.id_prioridad;
        opcion.textContent = prioridad.nombre;

        campoPrioridad.appendChild(opcion);
    });

    campoPrioridad.value = prioridadSeleccionada;
}

/*
|--------------------------------------------------------------------------
| Mostrar tickets
|--------------------------------------------------------------------------
*/

function mostrarTickets(tickets) {
    listaTickets.innerHTML = '';

    if (tickets.length === 0) {
        sinTickets.hidden = false;
        return;
    }

    sinTickets.hidden = true;

    tickets.forEach(function (ticket) {
        const fila = document.createElement('tr');

        const celdaFecha = document.createElement('td');
        const celdaDescripcion = document.createElement('td');
        const celdaSala = document.createElement('td');
        const celdaPrioridad = document.createElement('td');
        const celdaAccion = document.createElement('td');

        celdaFecha.textContent = formatearFecha(ticket.fecha);
        celdaDescripcion.textContent = ticket.descripcion;

        if (ticket.sala === null) {
            celdaSala.textContent = 'Sin sala';
        } else {
            celdaSala.textContent = ticket.sala;
        }

        celdaPrioridad.textContent = ticket.prioridad;

        const botonModificar = document.createElement('button');

        botonModificar.type = 'button';
        botonModificar.textContent = 'Modificar';

        botonModificar.addEventListener(
            'click',
            function () {
                cargarTicketEnFormulario(ticket);
            }
        );

        celdaAccion.appendChild(botonModificar);

        fila.appendChild(celdaFecha);
        fila.appendChild(celdaDescripcion);
        fila.appendChild(celdaSala);
        fila.appendChild(celdaPrioridad);
        fila.appendChild(celdaAccion);

        listaTickets.appendChild(fila);
    });
}

/*
|--------------------------------------------------------------------------
| Cargar un ticket para modificarlo
|--------------------------------------------------------------------------
*/

function cargarTicketEnFormulario(ticket) {
    campoIdTicket.value = ticket.id_ticket;
    campoDescripcion.value = ticket.descripcion;

    if (ticket.sala === null) {
        campoSala.value = '';
    } else {
        campoSala.value = ticket.sala;
    }

    campoPrioridad.value = ticket.id_prioridad;

    tituloFormulario.textContent = 'Modificar ticket';
    botonGuardar.textContent = 'Guardar cambios';
    botonCancelar.hidden = false;

    campoDescripcion.focus();
}

/*
|--------------------------------------------------------------------------
| Volver al formulario de creación
|--------------------------------------------------------------------------
*/

function volverModoCreacion() {
    formulario.reset();

    campoIdTicket.value = '';

    tituloFormulario.textContent = 'Crear ticket';
    botonGuardar.textContent = 'Crear ticket';
    botonCancelar.hidden = true;
}

/*
|--------------------------------------------------------------------------
| Bloquear formulario
|--------------------------------------------------------------------------
*/

function bloquearFormulario(bloquear) {
    campoDescripcion.disabled = bloquear;
    campoSala.disabled = bloquear;
    campoPrioridad.disabled = bloquear;
    botonGuardar.disabled = bloquear;
    botonCancelar.disabled = bloquear;

    if (bloquear === true) {
        botonGuardar.textContent = 'Guardando...';
    } else if (campoIdTicket.value === '') {
        botonGuardar.textContent = 'Crear ticket';
    } else {
        botonGuardar.textContent = 'Guardar cambios';
    }
}

/*
|--------------------------------------------------------------------------
| Mensajes
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Formatear fecha
|--------------------------------------------------------------------------
*/

function formatearFecha(fecha) {
    const fechaJavaScript = new Date(
        fecha.replace(' ', 'T')
    );

    return fechaJavaScript.toLocaleString('es-UY');
}
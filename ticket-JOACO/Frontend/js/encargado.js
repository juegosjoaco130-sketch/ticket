const API_URL = 'http://localhost:8000/api';

const formulario = document.getElementById('formulario-ticket');
const campoIdTicket = document.getElementById('id-ticket');
const campoDescripcion = document.getElementById('descripcion');
const campoSala = document.getElementById('sala');
const campoPrioridad = document.getElementById('prioridad');
const campoEstado = document.getElementById('estado');

const contenedorEstado = document.getElementById(
    'contenedor-estado'
);

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

cargarDatos();

formulario.addEventListener(
    'submit',
    async function (eventoEnvio) {
        eventoEnvio.preventDefault();

        ocultarMensajes();

        const idTicket = campoIdTicket.value;
        const descripcion = campoDescripcion.value.trim();
        const sala = campoSala.value;
        const idPrioridad = campoPrioridad.value;
        const idEstado = campoEstado.value;

        if (descripcion === '' || idPrioridad === '') {
            mostrarError(
                'Debes completar la descripción y la prioridad.'
            );

            return;
        }

        if (idTicket !== '' && idEstado === '') {
            mostrarError(
                'Debes seleccionar el estado.'
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
            datosTicket.id_estado = Number(idEstado);
        }

        try {
            const respuesta = await fetch(
                API_URL + '/encargado/tickets',
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

            volverModoCreacion();

            mensajeExito.textContent = datos.mensaje;
            mensajeExito.hidden = false;

            await cargarDatos();
        } catch (error) {
            mostrarError(error.message);
        } finally {
            bloquearFormulario(false);
        }
    }
);

botonCancelar.addEventListener('click', function () {
    volverModoCreacion();
    ocultarMensajes();
});

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

async function cargarDatos() {
    try {
        const respuesta = await fetch(
            API_URL + '/encargado/tickets',
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

        cargarOpciones(
            campoPrioridad,
            datos.prioridades,
            'id_prioridad'
        );

        cargarOpciones(
            campoEstado,
            datos.estados,
            'id_estado'
        );

        mostrarTickets(datos.tickets);
    } catch (error) {
        mostrarError(error.message);
    }
}


function cargarPrioridades(prioridades)
{
    campoPrioridad.innerHTML =
        '<option value="">Seleccione una prioridad</option>';

    prioridades.forEach(function (prioridad) {
        const opcion = document.createElement('option');

        opcion.value = prioridad.id_prioridad;
        opcion.textContent = prioridad.nombre;

        campoPrioridad.appendChild(opcion);
    });
}

function cargarOpciones(
    select,
    elementos,
    nombreId
) {
    select.innerHTML = '';

    const primeraOpcion = document.createElement('option');

    primeraOpcion.value = '';
    primeraOpcion.textContent = 'Selecciona una opción';

    select.appendChild(primeraOpcion);

    elementos.forEach(function (elemento) {
        const opcion = document.createElement('option');

        opcion.value = elemento[nombreId];
        opcion.textContent = elemento.nombre;

        select.appendChild(opcion);
    });
}

function mostrarTickets(tickets) {
    listaTickets.innerHTML = '';

    if (tickets.length === 0) {
        sinTickets.hidden = false;
        return;
    }

    sinTickets.hidden = true;

    tickets.forEach(function (ticket) {
        const fila = document.createElement('tr');

        agregarCelda(fila, formatearFecha(ticket.fecha));
        agregarCelda(fila, ticket.creador);
        agregarCelda(fila, ticket.descripcion);

        if (ticket.sala === null) {
            agregarCelda(fila, 'Sin sala');
        } else {
            agregarCelda(fila, ticket.sala);
        }

        agregarCelda(fila, ticket.prioridad);
        agregarCelda(fila, ticket.estado);

        const celdaAccion = document.createElement('td');

        if (esModificable(ticket.estado)) {
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
        } else {
            celdaAccion.textContent = 'No modificable';
        }

        fila.appendChild(celdaAccion);
        listaTickets.appendChild(fila);
    });
}

function agregarCelda(fila, contenido) {
    const celda = document.createElement('td');

    celda.textContent = contenido;

    fila.appendChild(celda);
}

function esModificable(estado) {
    const estadoNormalizado = estado
        .trim()
        .toLowerCase();

    return (
        estadoNormalizado === 'abierto' ||
        estadoNormalizado === 'en proceso'
    );
}

function cargarTicketEnFormulario(ticket) {
    campoIdTicket.value = ticket.id_ticket;
    campoDescripcion.value = ticket.descripcion;
    campoPrioridad.value = ticket.id_prioridad;
    campoEstado.value = ticket.id_estado;

    if (ticket.sala === null) {
        campoSala.value = '';
    } else {
        campoSala.value = ticket.sala;
    }

    tituloFormulario.textContent = 'Modificar ticket';
    botonGuardar.textContent = 'Guardar cambios';
    botonCancelar.hidden = false;
    contenedorEstado.hidden = false;

    campoDescripcion.focus();
}

function volverModoCreacion() {
    formulario.reset();

    campoIdTicket.value = '';

    tituloFormulario.textContent = 'Crear ticket';
    botonGuardar.textContent = 'Crear ticket';
    botonCancelar.hidden = true;
    contenedorEstado.hidden = true;
}

function bloquearFormulario(bloquear) {
    campoDescripcion.disabled = bloquear;
    campoSala.disabled = bloquear;
    campoPrioridad.disabled = bloquear;
    campoEstado.disabled = bloquear;
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

function formatearFecha(fecha) {
    const fechaJavaScript = new Date(
        fecha.replace(' ', 'T')
    );

    return fechaJavaScript.toLocaleString('es-UY');
}
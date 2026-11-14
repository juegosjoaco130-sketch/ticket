const API_URL = 'http://localhost:8000/api';

const formulario = document.getElementById('formulario-pedido');
const campoIdPedido = document.getElementById('id-pedido');
const campoEstado = document.getElementById('estado');
const resumenPedido = document.getElementById('resumen-pedido');

const tituloFormulario = document.getElementById(
    'titulo-formulario'
);

const listaPedidos = document.getElementById('lista-pedidos');
const sinPedidos = document.getElementById('sin-pedidos');

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

let estadosDisponibles = [];

/*
|--------------------------------------------------------------------------
| Inicio
|--------------------------------------------------------------------------
*/

cargarDatos();

/*
|--------------------------------------------------------------------------
| Cambiar el estado de un pedido
|--------------------------------------------------------------------------
*/

formulario.addEventListener(
    'submit',
    async function (eventoEnvio) {
        eventoEnvio.preventDefault();

        ocultarMensajes();

        const idPedido = campoIdPedido.value;
        const idEstado = campoEstado.value;

        if (idPedido === '') {
            mostrarError(
                'Primero seleccioná un pedido de la lista.'
            );

            return;
        }

        if (idEstado === '') {
            mostrarError(
                'Debes seleccionar el estado.'
            );

            return;
        }

        bloquearFormulario(true);

        try {
            const respuesta = await fetch(
                API_URL + '/pedidos',
                {
                    method: 'PUT',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    credentials: 'include',

                    body: JSON.stringify({
                        id_pedido: Number(idPedido),
                        id_estado: Number(idEstado)
                    })
                }
            );

            const datos = await respuesta.json();

            if (!respuesta.ok) {
                throw new Error(
                    datos.error ||
                    'No fue posible guardar el estado.'
                );
            }

            mensajeExito.textContent = datos.mensaje;
            mensajeExito.hidden = false;

            limpiarSeleccion();

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
| Cancelar selección
|--------------------------------------------------------------------------
*/

botonCancelar.addEventListener('click', function () {
    limpiarSeleccion();
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
| Obtener pedidos asignados y estados
|--------------------------------------------------------------------------
*/

async function cargarDatos() {
    try {
        const respuesta = await fetch(
            API_URL + '/pedidos',
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
                'No fue posible obtener los pedidos.'
            );
        }

        cargarEstados(datos.estados);
        mostrarPedidos(datos.pedidos);
    } catch (error) {
        mostrarError(error.message);
    }
}

/*
|--------------------------------------------------------------------------
| Mostrar estados
|--------------------------------------------------------------------------
*/

function cargarEstados(estados) {
    estadosDisponibles = estados;

    const estadoSeleccionado = campoEstado.value;

    campoEstado.replaceChildren();

    const primeraOpcion = document.createElement('option');

    primeraOpcion.value = '';
    primeraOpcion.textContent = 'Selecciona un estado';

    campoEstado.appendChild(primeraOpcion);

    estados.forEach(function (estado) {
        const opcion = document.createElement('option');

        opcion.value = estado.id_estado;
        opcion.textContent = estado.nombre;

        campoEstado.appendChild(opcion);
    });

    campoEstado.value = estadoSeleccionado;
}

/*
|--------------------------------------------------------------------------
| Mostrar pedidos
|--------------------------------------------------------------------------
*/

function mostrarPedidos(pedidos) {
    listaPedidos.replaceChildren();

    if (pedidos.length === 0) {
        sinPedidos.hidden = false;
        return;
    }

    sinPedidos.hidden = true;

    pedidos.forEach(function (pedido) {
        const fila = document.createElement('tr');

        agregarCelda(fila, formatearFecha(pedido.fecha));
        agregarCelda(fila, pedido.cliente);
        agregarCelda(fila, pedido.telefono);
        agregarCelda(fila, pedido.direccion);
        agregarCelda(fila, pedido.descripcion);
        agregarCelda(fila, pedido.prioridad);
        agregarCelda(fila, pedido.estado);

        const celdaAccion = document.createElement('td');
        const botonEstado = document.createElement('button');

        botonEstado.type = 'button';
        botonEstado.textContent = 'Cambiar estado';

        botonEstado.addEventListener(
            'click',
            function () {
                cargarPedidoEnFormulario(pedido);
            }
        );

        celdaAccion.appendChild(botonEstado);

        fila.appendChild(celdaAccion);
        listaPedidos.appendChild(fila);
    });
}

function agregarCelda(fila, contenido) {
    const celda = document.createElement('td');

    celda.textContent = contenido;

    fila.appendChild(celda);
}

/*
|--------------------------------------------------------------------------
| Cargar un pedido para cambiar su estado
|--------------------------------------------------------------------------
*/

function buscarEstadoPorNombre(nombre) {
    return estadosDisponibles.find(function (estado) {
        return estado.nombre === nombre;
    });
}

//B05-corrección: el repartidor solo avanza En preparación -> En camino
//-> Entregado. El backend vuelve a validar (nunca confiar en el frontend).
function estadoSiguientePermitido(estadoActual) {
    if (estadoActual === 'En preparación') {
        return 'En camino';
    }

    if (estadoActual === 'En camino') {
        return 'Entregado';
    }

    return null;
}

function cargarPedidoEnFormulario(pedido) {
    campoIdPedido.value = pedido.id_pedido;

    const siguiente = estadoSiguientePermitido(pedido.estado);
    const estadoSiguiente = siguiente === null
        ? undefined
        : buscarEstadoPorNombre(siguiente);

    campoEstado.replaceChildren();

    const primeraOpcion = document.createElement('option');

    primeraOpcion.value = '';
    primeraOpcion.textContent = 'Selecciona un estado';

    campoEstado.appendChild(primeraOpcion);

    if (estadoSiguiente === undefined) {
        resumenPedido.textContent = 'Pedido de ' + pedido.cliente +
            ' — ' + pedido.direccion + ' (' + pedido.estado +
            '). Ya fue entregado y no admite cambios de estado.';

        campoEstado.disabled = true;
        botonGuardar.disabled = true;
    } else {
        const opcion = document.createElement('option');

        opcion.value = estadoSiguiente.id_estado;
        opcion.textContent = estadoSiguiente.nombre;

        campoEstado.appendChild(opcion);
        campoEstado.value = estadoSiguiente.id_estado;

        resumenPedido.textContent = 'Pedido de ' + pedido.cliente +
            ' — ' + pedido.direccion + ' (' + pedido.estado + ').';

        campoEstado.disabled = false;
        botonGuardar.disabled = false;
    }

    tituloFormulario.textContent = 'Cambiar estado';
    botonCancelar.hidden = false;

    campoEstado.focus();
}

/*
|--------------------------------------------------------------------------
| Limpiar selección
|--------------------------------------------------------------------------
*/

function limpiarSeleccion() {
    formulario.reset();

    cargarEstados(estadosDisponibles);

    campoEstado.disabled = false;
    botonGuardar.disabled = false;

    campoIdPedido.value = '';

    resumenPedido.textContent =
        'Seleccioná un pedido de la lista para cambiar su estado.';

    tituloFormulario.textContent = 'Cambiar estado';
    botonCancelar.hidden = true;
}

/*
|--------------------------------------------------------------------------
| Bloquear formulario
|--------------------------------------------------------------------------
*/

function bloquearFormulario(bloquear) {
    campoEstado.disabled = bloquear;
    botonGuardar.disabled = bloquear;
    botonCancelar.disabled = bloquear;

    if (bloquear === true) {
        botonGuardar.textContent = 'Guardando...';
    } else {
        botonGuardar.textContent = 'Guardar estado';
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

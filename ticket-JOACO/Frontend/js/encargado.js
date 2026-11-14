const API_URL = 'http://localhost:8000/api';

const formulario = document.getElementById('formulario-pedido');
const campoIdPedido = document.getElementById('id-pedido');
const campoCliente = document.getElementById('cliente');
const campoTelefono = document.getElementById('telefono');
const campoDireccion = document.getElementById('direccion');
const campoDescripcion = document.getElementById('descripcion');
const campoPrioridad = document.getElementById('prioridad');
const campoEstado = document.getElementById('estado');
const campoRepartidor = document.getElementById('repartidor');

const contenedorEstado = document.getElementById(
    'contenedor-estado'
);

const contenedorRepartidor = document.getElementById(
    'contenedor-repartidor'
);

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

cargarDatos();

formulario.addEventListener(
    'submit',
    async function (eventoEnvio) {
        eventoEnvio.preventDefault();

        ocultarMensajes();

        const idPedido = campoIdPedido.value;
        const cliente = campoCliente.value.trim();
        const telefono = campoTelefono.value.trim();
        const direccion = campoDireccion.value.trim();
        const descripcion = campoDescripcion.value.trim();
        const idPrioridad = campoPrioridad.value;
        const idEstado = campoEstado.value;
        const idRepartidor = campoRepartidor.value;

        if (
            cliente === '' ||
            telefono === '' ||
            direccion === '' ||
            descripcion === '' ||
            idPrioridad === ''
        ) {
            mostrarError(
                'Debes completar cliente, teléfono, dirección, descripción y prioridad.'
            );

            return;
        }

        if (idPedido !== '' && idEstado === '') {
            mostrarError(
                'Debes seleccionar el estado.'
            );

            return;
        }

        bloquearFormulario(true);

        const datosPedido = {
            cliente: cliente,
            telefono: telefono,
            direccion: direccion,
            descripcion: descripcion,
            id_prioridad: Number(idPrioridad)
        };

        let metodo = 'POST';

        if (idPedido !== '') {
            metodo = 'PUT';

            datosPedido.id_pedido = Number(idPedido);
            datosPedido.id_estado = Number(idEstado);

            if (idRepartidor === '') {
                datosPedido.id_repartidor = null;
            } else {
                datosPedido.id_repartidor = Number(idRepartidor);
            }
        }

        try {
            const respuesta = await fetch(
                API_URL + '/encargado/pedidos',
                {
                    method: metodo,

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    credentials: 'include',

                    body: JSON.stringify(datosPedido)
                }
            );

            const datos = await respuesta.json();

            if (!respuesta.ok) {
                throw new Error(
                    datos.error ||
                    'No fue posible guardar el pedido.'
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
            API_URL + '/encargado/pedidos',
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

        await cargarRepartidores();

        mostrarPedidos(datos.pedidos);
    } catch (error) {
        mostrarError(error.message);
    }
}

async function cargarRepartidores() {
    const respuesta = await fetch(
        API_URL + '/encargado/repartidores',
        {
            method: 'GET',
            credentials: 'include'
        }
    );

    const datos = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            datos.error ||
            'No fue posible obtener los repartidores.'
        );
    }

    campoRepartidor.replaceChildren();

    const primeraOpcion = document.createElement('option');

    primeraOpcion.value = '';
    primeraOpcion.textContent = 'Sin asignar';

    campoRepartidor.appendChild(primeraOpcion);

    datos.repartidores.forEach(function (repartidor) {
        const opcion = document.createElement('option');

        opcion.value = repartidor.id_usuario;
        opcion.textContent = repartidor.nombre + ' ' +
            repartidor.apellido + ' (' + repartidor.usuario + ')';

        campoRepartidor.appendChild(opcion);
    });
}

function cargarOpciones(
    select,
    elementos,
    nombreId
) {
    select.replaceChildren();

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

        if (pedido.id_repartidor === null) {
            agregarCelda(fila, 'Sin asignar');
        } else {
            agregarCelda(
                fila,
                pedido.repartidor + ' (' + pedido.repartidor_usuario + ')'
            );
        }

        const celdaAccion = document.createElement('td');
        const botonModificar = document.createElement('button');

        botonModificar.type = 'button';
        botonModificar.textContent = 'Modificar';

        botonModificar.addEventListener(
            'click',
            function () {
                cargarPedidoEnFormulario(pedido);
            }
        );

        celdaAccion.appendChild(botonModificar);

        fila.appendChild(celdaAccion);
        listaPedidos.appendChild(fila);
    });
}

function agregarCelda(fila, contenido) {
    const celda = document.createElement('td');

    celda.textContent = contenido;

    fila.appendChild(celda);
}

function cargarPedidoEnFormulario(pedido) {
    campoIdPedido.value = pedido.id_pedido;
    campoCliente.value = pedido.cliente;
    campoTelefono.value = pedido.telefono;
    campoDireccion.value = pedido.direccion;
    campoDescripcion.value = pedido.descripcion;
    campoPrioridad.value = pedido.id_prioridad;
    campoEstado.value = pedido.id_estado;

    if (pedido.id_repartidor === null) {
        campoRepartidor.value = '';
    } else {
        campoRepartidor.value = pedido.id_repartidor;
    }

    tituloFormulario.textContent = 'Modificar pedido';
    botonGuardar.textContent = 'Guardar cambios';
    botonCancelar.hidden = false;
    contenedorEstado.hidden = false;
    contenedorRepartidor.hidden = false;

    campoCliente.focus();
}

function volverModoCreacion() {
    formulario.reset();

    campoIdPedido.value = '';

    tituloFormulario.textContent = 'Crear pedido';
    botonGuardar.textContent = 'Crear pedido';
    botonCancelar.hidden = true;
    contenedorEstado.hidden = true;
    contenedorRepartidor.hidden = true;
}

function bloquearFormulario(bloquear) {
    campoCliente.disabled = bloquear;
    campoTelefono.disabled = bloquear;
    campoDireccion.disabled = bloquear;
    campoDescripcion.disabled = bloquear;
    campoPrioridad.disabled = bloquear;
    campoEstado.disabled = bloquear;
    campoRepartidor.disabled = bloquear;
    botonGuardar.disabled = bloquear;
    botonCancelar.disabled = bloquear;

    if (bloquear === true) {
        botonGuardar.textContent = 'Guardando...';
    } else if (campoIdPedido.value === '') {
        botonGuardar.textContent = 'Crear pedido';
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

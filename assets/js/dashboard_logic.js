document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Lógica para las tarjetas y el Gráfico
    fetch('../models/get_estadisticas.php')
        .then(response => {
            if (!response.ok) throw new Error("Error en red");
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Actualizar números en las tarjetas superiores
                document.getElementById('total-empleados').innerText = data.totales.empleados;
                document.getElementById('total-asistencias-hoy').innerText = data.totales.asistencias;
                
                // Configurar Gráfico de Barras
                const ctx = document.getElementById('asistenciaChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.grafico.map(g => g.fecha),
                        datasets: [{
                            label: 'Asistencias',
                            data: data.grafico.map(g => g.total),
                            backgroundColor: 'rgba(13, 110, 253, 0.5)',
                            borderColor: '#0d6efd',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }
        })
        .catch(error => console.error('Error al cargar estadísticas:', error));

    // 2. Cargar registros recientes (Marcaciones de Hoy)
    fetch('../models/get_recientes.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const lista = document.getElementById('lista-recientes');
                lista.innerHTML = ''; // Limpiar el contenedor

                data.data.forEach(reg => {
                    const esTardanza = reg.horas_tard !== "00:00:00";
                    const badgeClass = esTardanza ? 'bg-danger' : 'bg-success';
                    const estadoText = esTardanza ? 'Tardanza' : 'Puntual';

                    lista.innerHTML += `
                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold">${reg.nomb_empl} ${reg.apat_empl}</h6>
                                <small class="text-muted">${reg.fech_ingr}</small>
                            </div>
                            <span class="badge ${badgeClass}">${estadoText}</span>
                        </div>
                    `;
                });
            }
        })
        .catch(error => console.error('Error al cargar recientes:', error));
});
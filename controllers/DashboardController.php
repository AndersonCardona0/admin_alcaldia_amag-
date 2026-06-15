<?php

/**
 * DashboardController.php
 * Intermediario entre los modelos de datos y la vista del panel principal.
 * No contiene lógica SQL ni etiquetas HTML: solo orquesta y delega.
 */
class DashboardController
{
    private EquipoModel $equipoModel;
    private ZonaModel   $zonaModel;

    public function __construct()
    {
        $this->equipoModel = new EquipoModel();
        $this->zonaModel   = new ZonaModel();
    }

    /**
     * Recopila los contadores globales y el listado de zonas,
     * luego inyecta las variables en la vista del dashboard.
     */
    public function mostrar(): void
    {
        // Contadores estadísticos para las tarjetas del panel
        $stats = [
            'total'            => $this->equipoModel->totalEquipos(),
            'operativos'       => $this->equipoModel->totalOperativos(),
            'en_mantenimiento' => $this->equipoModel->totalEnMantenimiento(),
        ];

        // Listado de zonas con encargado para la tabla "Zonas en Sondeo"
        $zonas = $this->zonaModel->obtenerTodasConEncargado();

        // Las variables $stats y $zonas quedan disponibles en el scope de la vista incluida
        require_once __DIR__ . '/../views/dashboard.php';
    }
}

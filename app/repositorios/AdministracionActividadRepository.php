<?php
/** Consultas de solo lectura para la actividad visible en el panel administrativo. */
final class AdministracionActividadRepository
{
    public function __construct(private PDO $pdo) {}

    public function resumen(int $usuarioId): array
    {
        $campanas = $this->pdo->prepare('SELECT COUNT(*) FROM camp WHERE owner_id = :id');
        $campanas->execute(['id' => $usuarioId]);
        $donaciones = $this->pdo->prepare('SELECT COUNT(*) FROM donacion WHERE id_usuario = :id');
        $donaciones->execute(['id' => $usuarioId]);
        return ['campanas' => (int) $campanas->fetchColumn(), 'donaciones' => (int) $donaciones->fetchColumn()];
    }

    public function campanas(int $usuarioId): array
    {
        // La consulta existente resume los objetivos y omite el CBU.
        require_once __DIR__ . '/CampRepository.php';
        return (new CampRepository($this->pdo))->buscarPorCreador($usuarioId);
    }

    public function donaciones(int $usuarioId): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT d.id, d.id_camp, c.nombre AS campana_nombre, c.monetaria,
                    c.estado AS campana_estado, d.cantidad, d.fecha_hora,
                    ci.nombre AS categoria_insumo, sci.nombre AS subcategoria_insumo
             FROM donacion d
             INNER JOIN camp c ON c.id = d.id_camp
             LEFT JOIN categoria_insumos ci ON ci.id = d.id_categoria_insumos
             LEFT JOIN sub_categoria_insumos sci ON sci.id = d.id_sub_categoria_insumos
             WHERE d.id_usuario = :id
             ORDER BY d.fecha_hora DESC, d.id DESC'
        );
        $consulta->execute(['id' => $usuarioId]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}

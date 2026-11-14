<?php
function estadoPago(string $estado, string $detalle): string
{
    if ($detalle === 'partially_refunded') return 'Reembolsado parcialmente';
    return match ($estado) {
        'approved', 'processed' => $detalle === 'accredited' ? 'Aporte confirmado' : 'En verificación',
        'rejected', 'failed' => 'Pago rechazado',
        'cancelled', 'canceled', 'expired' => 'Pago cancelado o vencido',
        'refunded' => 'Pago reembolsado',
        'charged_back' => 'Pago con contracargo',
        default => 'Pendiente de confirmación',
    };
}

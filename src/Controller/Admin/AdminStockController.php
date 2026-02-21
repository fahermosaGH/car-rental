<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin', name: 'api_admin_')]
final class AdminStockController extends AbstractController
{
    /**
     * Devuelve stock "real disponible ahora":
     * unidades status=available menos unidades ocupadas por reservas activas ahora.
     *
     * Respuesta: [{ locationId, vehicleId, qty }]
     */
    #[Route('/stock/available-now', name: 'stock_available_now', methods: ['GET'])]
    public function availableNow(Connection $db, Request $req): JsonResponse
    {
        $locationId = (int) $req->query->get('locationId', 0);
        $vehicleId = (int) $req->query->get('vehicleId', 0);

        // Ajustá estos estados si tu Reservation maneja otros nombres.
        // La idea: excluir canceladas.
        $excludedReservationStatuses = ['cancelled'];

        /**
         * IMPORTANTE:
         * - vehicle_unit: id, vehicle_id, location_id, status
         * - reservation: vehicle_unit_id, start_at, end_at, status
         *
         * Si tus columnas tienen otros nombres (p. ej. startAt/endAt),
         * decímelo y te lo adapto 1:1, pero en tu BD suele ser start_at/end_at.
         */

        $sql = "
            SELECT
              vu.location_id AS locationId,
              vu.vehicle_id AS vehicleId,
              COUNT(vu.id) AS qty
            FROM vehicle_unit vu
            LEFT JOIN reservation r
              ON r.vehicle_unit_id = vu.id
             AND r.start_at <= NOW()
             AND r.end_at >= NOW()
             AND r.status NOT IN (?)
            WHERE vu.status = 'available'
        ";

        $params = [$excludedReservationStatuses];
        $types  = [Connection::PARAM_STR_ARRAY];

        if ($locationId > 0) {
            $sql .= " AND vu.location_id = ? ";
            $params[] = $locationId;
        }
        if ($vehicleId > 0) {
            $sql .= " AND vu.vehicle_id = ? ";
            $params[] = $vehicleId;
        }

        // Si hay reserva activa, r.id NO es NULL => esa unidad está ocupada => no contar
        $sql .= "
            AND r.id IS NULL
            GROUP BY vu.location_id, vu.vehicle_id
            ORDER BY vu.location_id, vu.vehicle_id
        ";

        $rows = $db->fetchAllAssociative($sql, $params, $types);

        // Normalizar tipos
        foreach ($rows as &$row) {
            $row['locationId'] = (int) $row['locationId'];
            $row['vehicleId'] = (int) $row['vehicleId'];
            $row['qty'] = (int) $row['qty'];
        }

        return $this->json($rows);
    }
}
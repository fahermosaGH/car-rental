<?php

namespace App\Controller\Api;

use App\Entity\Reservation;
use App\Entity\ReservationExtra;
use App\Entity\User;
use App\Repository\VehicleRepository;
use App\Repository\LocationRepository;
use App\Service\ReservationValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/api/reservations')]
class ReservationController extends AbstractController
{
    #[Route('', name: 'api_reservations_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        VehicleRepository $vehicleRepo,
        LocationRepository $locationRepo,
        ReservationValidator $validator,
        #[CurrentUser] ?User $user = null
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'JSON inválido'], 400);
        }

        if (!isset(
            $data['vehicleId'],
            $data['pickupLocationId'],
            $data['dropoffLocationId'],
            $data['startAt'],
            $data['endAt']
        )) {
            return $this->json(['error' => 'Faltan campos obligatorios'], 422);
        }

        if (!$user) {
            return $this->json(['error' => 'Debés iniciar sesión para crear una reserva.'], 401);
        }

        if (!$user->isProfileComplete()) {
            return $this->json([
                'error' => 'Para confirmar una reserva necesitás completar tu perfil (datos personales y licencia).',
                'code'  => 'PROFILE_INCOMPLETE',
            ], 422);
        }

        $vehicle = $vehicleRepo->find($data['vehicleId']);
        $pickup  = $locationRepo->find($data['pickupLocationId']);
        $dropoff = $locationRepo->find($data['dropoffLocationId']);

        if (!$vehicle || !$pickup || !$dropoff) {
            return $this->json(['error' => 'Datos de vehículo o sucursal inválidos'], 422);
        }

        try {
            $startAt = new \DateTimeImmutable($data['startAt']);
            $endAt   = new \DateTimeImmutable($data['endAt']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Formato de fecha inválido'], 400);
        }

        if ($startAt >= $endAt) {
            return $this->json(['error' => 'La fecha de fin debe ser posterior a la de inicio'], 422);
        }

        $available = $validator->isAvailable(
            $vehicle->getId(),
            $pickup->getId(),
            $startAt,
            $endAt,
            null
        );

        if (!$available) {
            return $this->json([
                'error' => 'El vehículo no está disponible en las fechas seleccionadas (sucursal/stock).'
            ], 409);
        }

        $reservation = new Reservation();
        $reservation->setVehicle($vehicle);
        $reservation->setPickupLocation($pickup);
        $reservation->setDropoffLocation($dropoff);
        $reservation->setStartAt($startAt);
        $reservation->setEndAt($endAt);
        $reservation->setStatus('confirmed');
        $reservation->setTotalPrice($data['totalPrice'] ?? 0);

        if ($user instanceof User) {
            $reservation->setUser($user);
        }

        if (!empty($data['extras']) && is_array($data['extras'])) {
            foreach ($data['extras'] as $extraData) {
                if (!empty($extraData['name']) && isset($extraData['price'])) {
                    $extra = new ReservationExtra();
                    $extra->setName($extraData['name']);
                    $extra->setPrice($extraData['price']);
                    $reservation->addExtra($extra);
                }
            }
        }

        $em->persist($reservation);
        $em->flush();

        return $this->json([
            'message' => '✅ Reserva creada correctamente',
            'id' => $reservation->getId(),
        ], 201);
    }

    #[Route('', name: 'api_reservations_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): Response
    {
        $reservas = $em->getRepository(Reservation::class)->findAll();

        $data = array_map(fn(Reservation $r) => [
            'id' => $r->getId(),
            'vehicle' => (string)$r->getVehicle(),
            'pickupLocation' => (string)$r->getPickupLocation(),
            'dropoffLocation' => (string)$r->getDropoffLocation(),
            'startAt' => $r->getStartAt()->format('Y-m-d'),
            'endAt' => $r->getEndAt()->format('Y-m-d'),
            'totalPrice' => $r->getTotalPrice(),
            'status' => $r->getStatus(),
        ], $reservas);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_reservations_show', methods: ['GET'])]
    public function show(
        Reservation $reservation,
        #[CurrentUser] ?User $user = null
    ): Response {
        if ($reservation->getUser() && $user && $reservation->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $vehicle = $reservation->getVehicle();
        $pickup  = $reservation->getPickupLocation();
        $dropoff = $reservation->getDropoffLocation();

        $extras = [];
        foreach ($reservation->getExtras() as $extra) {
            $extras[] = [
                'name'  => $extra->getName(),
                'price' => $extra->getPrice(),
            ];
        }

        return $this->json([
            'id'                   => $reservation->getId(),
            'vehicleName'          => $vehicle
                ? trim(($vehicle->getBrand() ?? '') . ' ' . ($vehicle->getModel() ?? ''))
                : 'Vehículo',
            'category'             => $vehicle && $vehicle->getCategory()
                ? $vehicle->getCategory()->getName()
                : null,
            'pickupLocationName'   => $pickup?->getName() ?? 'Sucursal origen',
            'dropoffLocationName'  => $dropoff?->getName() ?? 'Sucursal destino',
            'startAt'              => $reservation->getStartAt()?->format('Y-m-d'),
            'endAt'                => $reservation->getEndAt()?->format('Y-m-d'),
            'totalPrice'           => $reservation->getTotalPrice(),
            'status'               => $reservation->getStatus(),
            'rating'               => $reservation->getRating(),
            'ratingComment'        => $reservation->getRatingComment(),
            'extras'               => $extras,
        ]);
    }

    #[Route('/{id}/send-voucher', name: 'api_reservations_send_voucher', methods: ['POST'])]
    public function sendVoucher(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        #[CurrentUser] ?User $user = null
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        /** @var Reservation|null $reservation */
        $reservation = $em->getRepository(Reservation::class)->find($id);
        if (!$reservation) {
            return $this->json(['error' => 'Reserva no encontrada'], 404);
        }

        if ($reservation->getUser() && $reservation->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $emailTo = $data['email'] ?? null;

        if (!$emailTo || !filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Email inválido'], 422);
        }

        $vehicle = $reservation->getVehicle();
        $pickup  = $reservation->getPickupLocation();
        $dropoff = $reservation->getDropoffLocation();

        $lines   = [];
        $lines[] = sprintf('Comprobante de reserva #%d', $reservation->getId());
        $lines[] = '';
        $lines[] = sprintf('Vehículo: %s %s',
            $vehicle?->getBrand() ?? '',
            $vehicle?->getModel() ?? ''
        );
        $lines[] = sprintf('Categoría: %s', $vehicle?->getCategory()?->getName() ?? 'N/A');
        $lines[] = '';
        $lines[] = sprintf('Retiro: %s - %s',
            $reservation->getStartAt()?->format('d/m/Y'),
            $pickup?->getName() ?? ''
        );
        $lines[] = sprintf('Devolución: %s - %s',
            $reservation->getEndAt()?->format('d/m/Y'),
            $dropoff?->getName() ?? ''
        );
        $lines[] = '';
        $lines[] = sprintf('Estado: %s', $reservation->getStatus());
        $lines[] = sprintf('Total: ARS %s', $reservation->getTotalPrice() ?? '0.00');
        $lines[] = '';
        $lines[] = 'Extras:';

        if (\count($reservation->getExtras()) === 0) {
            $lines[] = '- Sin extras seleccionados.';
        } else {
            foreach ($reservation->getExtras() as $extra) {
                $lines[] = sprintf('- %s: ARS %s', $extra->getName(), $extra->getPrice());
            }
        }

        $body = implode("\n", $lines);

        $email = (new Email())
            ->from('no-reply@car-rental.local')
            ->to($emailTo)
            ->subject(sprintf('Comprobante de reserva #%d', $reservation->getId()))
            ->text($body);

        $mailer->send($email);

        return $this->json([
            'message' => 'Comprobante enviado (simulado)',
            'email'   => $emailTo,
        ]);
    }

    #[Route('/{id}/cancel', name: 'api_reservations_cancel', methods: ['POST'])]
    public function cancel(
        int $id,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user = null
    ): JsonResponse {
        if (!$user) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        /** @var Reservation|null $reservation */
        $reservation = $em->getRepository(Reservation::class)->find($id);

        if (!$reservation || $reservation->getUser()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'Reserva no encontrada'], 404);
        }

        if ($reservation->getStatus() === 'cancelled') {
            return $this->json(['message' => 'La reserva ya está cancelada.'], 409);
        }

        $now     = new \DateTimeImmutable('now');
        $startAt = $reservation->getStartAt();

        $diff        = $now->diff($startAt);
        $daysToStart = (int) $diff->format('%r%a');

        $total          = $reservation->getTotalPrice() !== null ? (float) $reservation->getTotalPrice() : 0.0;
        $penaltyPercent = 0;
        $penaltyAmount  = '0.00';

        if ($daysToStart < 2) {
            return $this->json([
                'message' =>
                    'No podés cancelar la reserva desde la web con menos de 48 horas de anticipación. ' .
                    'Comunicate con atención al cliente; podrían aplicarse cargos según la política de la agencia.'
            ], 422);
        }

        if ($daysToStart <= 15) {
            $penaltyPercent = 20;
            $penaltyAmount  = number_format($total * 0.20, 2, '.', '');
        }

        $reservation->setStatus('cancelled');
        $em->flush();

        $message = $penaltyPercent > 0
            ? 'Reserva cancelada con un cargo del 20 % sobre el total.'
            : 'Reserva cancelada sin cargo.';

        return $this->json([
            'message'        => $message,
            'id'             => $reservation->getId(),
            'status'         => $reservation->getStatus(),
            'penaltyPercent' => $penaltyPercent,
            'penaltyAmount'  => $penaltyAmount,
            'daysToStart'    => $daysToStart,
        ]);
    }

    #[Route('/{id}/rating', name: 'api_reservations_rating', methods: ['POST'])]
    public function rating(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user = null
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        /** @var Reservation|null $reservation */
        $reservation = $em->getRepository(Reservation::class)->find($id);
        if (!$reservation) {
            return $this->json(['error' => 'Reserva no encontrada'], 404);
        }

        if ($reservation->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        // ✅ REGLA: solo se califica una reserva finalizada
        if ($reservation->getStatus() !== 'completed') {
            return $this->json(['error' => 'Solo podés calificar reservas finalizadas.'], 422);
        }

        // ✅ opcional: evitar doble calificación
        if ($reservation->getRating() !== null) {
            return $this->json(['error' => 'Esta reserva ya fue calificada.'], 409);
        }

        $data = json_decode($request->getContent(), true);
        $rating  = $data['rating'] ?? null;
        $comment = $data['comment'] ?? null;

        if (!$rating || $rating < 1 || $rating > 5) {
            return $this->json(['error' => 'Rating inválido (1-5)'], 422);
        }

        $reservation->setRating((int)$rating);
        $reservation->setRatingComment($comment);

        $em->flush();

        return $this->json(['message' => 'Calificación guardada']);
    }
}
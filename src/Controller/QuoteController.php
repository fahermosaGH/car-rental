<?php

namespace App\Controller;

use App\Entity\Location;
use App\Repository\LocationRepository;
use App\Service\AvailabilityService;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuoteController extends AbstractController
{
    public function __construct(
        private LocationRepository $locationRepo,          // <-- inyectamos el repo
        private AvailabilityService $availabilityService   // <-- y el servicio
    ) {}

    #[Route('/cotizar', name: 'app_quote', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        // Cargamos todas las ubicaciones para el select (orden por id asc)
        $locations = $this->locationRepo->findBy([], ['id' => 'ASC']);

        $errors = [];
        $results = [];
        $form = [
            'location_id' => $request->request->getInt('location_id', 0),
            'pickup'      => (string)$request->request->get('pickup', ''),
            'dropoff'     => (string)$request->request->get('dropoff', ''),
        ];

        $days = null;

        if ($request->isMethod('POST')) {
            if ($form['location_id'] <= 0) {
                $errors[] = 'Seleccioná una ubicación.';
            }

            $tz = new DateTimeZone($_ENV['APP_TIMEZONE'] ?? 'America/Argentina/Cordoba');
            $pickupDt  = $this->parseDdMmYyyy($form['pickup'], $tz);
            $dropoffDt = $this->parseDdMmYyyy($form['dropoff'], $tz);

            if (!$pickupDt)  { $errors[] = 'Fecha de retiro inválida (usa dd/mm/yyyy).'; }
            if (!$dropoffDt) { $errors[] = 'Fecha de devolución inválida (usa dd/mm/yyyy).'; }

            if ($pickupDt && $dropoffDt) {
                if ($dropoffDt <= $pickupDt) {
                    $errors[] = 'La devolución debe ser posterior al retiro.';
                } else {
                    $days = (int)$pickupDt->diff($dropoffDt)->format('%a');
                    if ($days < 1) {
                        $errors[] = 'La cantidad mínima es 1 día.';
                    }
                }
            }

            if (empty($errors)) {
                // Disponibilidad por ubicación (por ahora sin mirar fechas)
                $available = $this->availabilityService
                    ->availableVehiclesByLocation($form['location_id']);

                foreach ($available as $row) {
                    $v = $row['vehicle'];
                    $pricePerDay = null;

                    if (method_exists($v, 'getPricePerDay')) {
                        $pricePerDay = $v->getPricePerDay();
                    } elseif (method_exists($v, 'getDailyPrice')) {
                        $pricePerDay = $v->getDailyPrice();
                    }

                    $results[] = [
                        'vehicle' => $v,
                        'quantity' => (int)$row['quantity'],
                        'pricePerDay' => $pricePerDay,
                        'days' => $days,
                        'total' => ($pricePerDay !== null && $days !== null) ? $pricePerDay * $days : null,
                    ];
                }
            }
        }

        return $this->render('quote/index.html.twig', [
            'locations' => $locations,
            'form' => $form,
            'errors' => $errors,
            'results' => $results,
            'days' => $days,
        ]);
    }

    private function parseDdMmYyyy(string $value, DateTimeZone $tz): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') return null;
        $parts = explode('/', $value);
        if (count($parts) !== 3) return null;
        [$d, $m, $y] = $parts;
        if (!ctype_digit($d) || !ctype_digit($m) || !ctype_digit($y)) return null;
        $d = (int)$d; $m = (int)$m; $y = (int)$y;
        if (!checkdate($m, $d, $y)) return null;

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d 00:00:00', $y, $m, $d), $tz);
    }
}
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { CotizarService } from '../../services/cotizar.service';

interface ReservationExtraDto {
  name: string;
  price: string;
}

interface ReservationDetailDto {
  id: number;
  vehicleName: string;
  category: string | null;
  pickupLocationName: string;
  dropoffLocationName: string;
  startAt: string;
  endAt: string;
  totalPrice: string | null;
  status: string;
  extras: ReservationExtraDto[];
}

@Component({
  selector: 'app-confirmacion',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './confirmacion.component.html',
  styleUrls: ['./confirmacion.component.css'],
})
export class ConfirmacionComponent implements OnInit {
  reserva?: ReservationDetailDto;

  // 👇 estos nombres matchean el HTML
  loading = true;
  error = '';

  // email para enviar comprobante (coincide con [(ngModel)]="emailComprobante")
  emailComprobante: string = '';

  // estado de envío
  enviando = false;
  mensajeEnvio = '';
  errorEnvio = '';

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cotizarService: CotizarService
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id || Number.isNaN(id)) {
      this.error = 'ID de reserva inválido.';
      this.loading = false;
      return;
    }

    this.cotizarService.getReservationById(id).subscribe({
      next: (data) => {
        this.reserva = data;
        this.loading = false;
      },
      error: () => {
        this.error = 'No se pudo cargar la información de la reserva.';
        this.loading = false;
      },
    });
  }

  volverACotizar() {
    this.router.navigate(['/cotizar']);
  }

  irAMisReservas() {
    this.router.navigate(['/mis-reservas']);
  }

  // 👉 versión real, llama al backend /api/reservations/{id}/send-voucher
  enviarComprobante() {
    this.errorEnvio = '';
    this.mensajeEnvio = '';

    if (!this.reserva) {
      this.errorEnvio = 'No se encontró la reserva.';
      return;
    }

    const email = this.emailComprobante.trim();
    if (!email) {
      this.errorEnvio = 'Ingresá un email para enviar el comprobante.';
      return;
    }

    // validación mínima de formato
    const simpleRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!simpleRegex.test(email)) {
      this.errorEnvio = 'El formato del email no es válido.';
      return;
    }

    this.enviando = true;

    this.cotizarService.enviarComprobante(this.reserva.id, email).subscribe({
      next: (res) => {
        this.enviando = false;
        this.mensajeEnvio = res.message || `Comprobante enviado a ${res.email || email}.`;
      },
      error: () => {
        this.enviando = false;
        this.errorEnvio =
          'No se pudo enviar el comprobante. Intentá nuevamente en unos minutos.';
      },
    });
  }

  // alias por si algo viejo llamaba a este método
  enviarComprobanteSimulado() {
    this.enviarComprobante();
  }
}

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

  rating?: number;
  ratingComment?: string;
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

  loading = true;
  error = '';

  emailComprobante = '';
  enviando = false;
  mensajeEnvio = '';
  errorEnvio = '';

  estadoDesdeMisReservas = '';

  // RATING
  rating = 0;
  ratingComment = '';
  ratingEnviando = false;
  ratingMensaje = '';
  ratingError = '';

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cotizarService: CotizarService
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    this.estadoDesdeMisReservas =
      this.route.snapshot.queryParamMap.get('estado') || '';

    this.cotizarService.getReservationById(id).subscribe({
      next: (data) => {
        this.reserva = data;

        if (data.rating) this.rating = data.rating;
        if (data.ratingComment) this.ratingComment = data.ratingComment;

        this.loading = false;
      },
      error: () => {
        this.error = 'No se pudo cargar la reserva.';
        this.loading = false;
      },
    });
  }

  setRating(value: number) {
    this.rating = value;
  }

  guardarRating() {
    if (!this.reserva) return;

    this.ratingEnviando = true;
    this.ratingMensaje = '';
    this.ratingError = '';

    this.cotizarService.calificarReserva(this.reserva.id, {
      rating: this.rating,
      comment: this.ratingComment,
    }).subscribe({
      next: () => {
        this.ratingEnviando = false;
        this.ratingMensaje = '¡Gracias por tu calificación!';
      },
      error: () => {
        this.ratingEnviando = false;
        this.ratingError = 'No se pudo guardar la calificación.';
      }
    });
  }

  volverACotizar() {
    this.router.navigate(['/cotizar']);
  }

  irAMisReservas() {
    this.router.navigate(['/mis-reservas']);
  }

  enviarComprobante() {
    this.errorEnvio = '';
    this.mensajeEnvio = '';

    if (!this.reserva) return;

    const email = this.emailComprobante.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      this.errorEnvio = 'Ingresá un email válido.';
      return;
    }

    this.enviando = true;

    this.cotizarService.enviarComprobante(this.reserva.id, email).subscribe({
      next: (res) => {
        this.enviando = false;
        this.mensajeEnvio = res.message || 'Comprobante enviado.';
      },
      error: () => {
        this.enviando = false;
        this.errorEnvio = 'No se pudo enviar el comprobante.';
      },
    });
  }
}

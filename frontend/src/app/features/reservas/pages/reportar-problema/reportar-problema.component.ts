import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from '../../../../../environments/environment';
import { AuthService } from '../../../../core/services/auth.service';

@Component({
  selector: 'app-reportar-problema',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './reportar-problema.component.html',
  styleUrls: ['./reportar-problema.component.css'],
})
export class ReportarProblemaComponent {
  reservationId: number;
  description = '';
  sending = false;
  errorMsg = '';
  okMsg = '';

  private apiUrl = environment.apiUrl;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private http: HttpClient,
    private auth: AuthService
  ) {
    this.reservationId = Number(this.route.snapshot.paramMap.get('id'));
  }

  volver(): void {
    this.router.navigate(['/mis-reservas']);
  }

  enviar(): void {
    this.errorMsg = '';
    this.okMsg = '';

    if (!this.description.trim()) {
      this.errorMsg = 'Contanos qué pasó (descripción obligatoria).';
      return;
    }

    const headers = this.auth.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.auth.token}` })
      : undefined;

    this.sending = true;

    this.http
      .post(
        `${this.apiUrl}/incidents`,
        { reservationId: this.reservationId, description: this.description },
        { headers }
      )
      .subscribe({
        next: () => {
          this.sending = false;
          this.okMsg = 'Incidente enviado. El equipo de soporte lo va a revisar.';
          this.description = '';
        },
        error: (err) => {
          this.sending = false;
          this.errorMsg = err?.error?.error ?? 'No se pudo enviar el incidente.';
        },
      });
  }
}
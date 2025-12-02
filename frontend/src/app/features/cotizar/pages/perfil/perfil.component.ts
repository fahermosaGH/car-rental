import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../../../core/services/auth.service';

interface PerfilData {
  fullName: string;
  email: string;
  phone: string;
  documentNumber: string;
  birthDate: string;
  address: string;
  licenseNumber: string;
  licenseExpiry: string;
  licenseCountry: string;
}

@Component({
  selector: 'app-perfil',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './perfil.component.html',
  styleUrls: ['./perfil.component.css'],
})
export class PerfilComponent implements OnInit {
  private auth = inject(AuthService);

  perfil: PerfilData = {
    fullName: '',
    email: '',
    phone: '',
    documentNumber: '',
    birthDate: '',
    address: '',
    licenseNumber: '',
    licenseExpiry: '',
    licenseCountry: '',
  };

  loading = true;

  ngOnInit(): void {
    const token = this.auth.token;

    if (token) {
      try {
        const payloadPart = token.split('.')[1];
        const payloadJson = atob(payloadPart);
        const payload = JSON.parse(payloadJson);

        // Lo que seguro tenemos del JWT
        this.perfil.email = payload.username ?? payload.email ?? '';
        this.perfil.fullName = payload.fullName ?? payload.name ?? '';
      } catch {
        // si falla el decode del token, dejamos todo vacío
      }
    }

    // Por ahora, el resto de campos queda “No especificado” hasta CU13
    this.loading = false;
  }
}

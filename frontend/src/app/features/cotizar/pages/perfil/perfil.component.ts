import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import {
  AuthService,
  ProfileResponse,
  ProfileUpdatePayload,
} from '../../../../core/services/auth.service';

@Component({
  selector: 'app-perfil',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './perfil.component.html',
  styleUrls: ['./perfil.component.css'],
})
export class PerfilComponent implements OnInit {
  perfil: ProfileResponse | null = null;

  loading = true;
  saving = false;
  error = '';
  successMsg = '';

  constructor(private auth: AuthService) {}

  ngOnInit(): void {
    this.auth.getProfile().subscribe({
      next: (p) => {
        this.perfil = { ...p };
        this.loading = false;
      },
      error: () => {
        this.error = 'No se pudo cargar tu perfil.';
        this.loading = false;
      },
    });
  }

  guardar(): void {
    this.error = '';
    this.successMsg = '';

    if (!this.perfil) return;

    // Validaciones mínimas en front
    if (!this.perfil.firstName?.trim() || !this.perfil.lastName?.trim()) {
      this.error = 'Nombre y apellido son obligatorios.';
      return;
    }

    if (!this.perfil.phone?.trim()) {
      this.error = 'El teléfono es obligatorio.';
      return;
    }

    const payload: ProfileUpdatePayload = {
      firstName: this.perfil.firstName.trim(),
      lastName: this.perfil.lastName.trim(),
      phone: this.perfil.phone?.trim() || null,
      documentNumber: this.perfil.documentNumber?.trim() || null,
      birthDate: this.perfil.birthDate || null,      // viene como yyyy-MM-dd
      address: this.perfil.address?.trim() || null,
      licenseNumber: this.perfil.licenseNumber?.trim() || null,
      licenseCountry: this.perfil.licenseCountry?.trim() || null,
      licenseExpiry: this.perfil.licenseExpiry || null, // yyyy-MM-dd
    };

    this.saving = true;

    this.auth.updateProfile(payload).subscribe({
      next: (res) => {
        this.saving = false;
        this.perfil = { ...res };
        this.successMsg = 'Tus datos se guardaron correctamente.';
      },
      error: (err) => {
        this.saving = false;
        this.error =
          err?.error?.message ||
          'No se pudo guardar tu perfil. Intentá nuevamente.';
      },
    });
  }
}



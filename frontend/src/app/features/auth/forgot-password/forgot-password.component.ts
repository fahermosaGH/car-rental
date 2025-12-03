import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-forgot-password',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './forgot-password.component.html',
  styleUrls: ['./forgot-password.component.css'],
})
export class ForgotPasswordComponent {
  email = '';
  newPassword = '';
  confirmPassword = '';
  cargando = false;
  errorMsg = '';
  successMsg = '';

  constructor(private auth: AuthService) {}

  submit() {
    this.errorMsg = '';
    this.successMsg = '';

    if (!this.email) {
      this.errorMsg = 'Ingresá tu email.';
      return;
    }

    if (!this.newPassword || !this.confirmPassword) {
      this.errorMsg = 'Ingresá y confirmá la nueva contraseña.';
      return;
    }

    if (this.newPassword !== this.confirmPassword) {
      this.errorMsg = 'Las contraseñas no coinciden.';
      return;
    }

    this.cargando = true;

    this.auth.resetPassword(this.email, this.newPassword).subscribe({
      next: () => {
        this.cargando = false;
        this.successMsg = 'Contraseña actualizada correctamente.';
        this.newPassword = '';
        this.confirmPassword = '';
      },
      error: () => {
        this.cargando = false;
        this.errorMsg = 'No se pudo actualizar la contraseña.';
      },
    });
  }
}


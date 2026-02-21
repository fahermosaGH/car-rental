import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { RouterModule } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css'],
})
export class RegisterComponent {
  firstName = '';
  lastName = '';
  email = '';
  password = '';
  confirmPassword = '';

  cargando = false;
  errorMsg = '';
  successMsg = '';

  constructor(private auth: AuthService, private router: Router) {}

  submit(): void {
    this.errorMsg = '';
    this.successMsg = '';

    if (!this.firstName || !this.lastName || !this.email || !this.password || !this.confirmPassword) {
      this.errorMsg = 'Completá todos los campos.';
      return;
    }

    if (this.password !== this.confirmPassword) {
      this.errorMsg = 'Las contraseñas no coinciden.';
      return;
    }

    if (this.password.length < 8) {
      this.errorMsg = 'La contraseña debe tener al menos 8 caracteres.';
      return;
    }

    if (this.password.length > 64) {
      this.errorMsg = 'La contraseña no puede superar los 64 caracteres.';
      return;
    }

    const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/;

    if (!strongRegex.test(this.password)) {
      this.errorMsg = 'Debe contener al menos una mayúscula, una minúscula y un número.';
      return;
    }

    this.cargando = true;

    this.auth
      .register({
        firstName: this.firstName,
        lastName: this.lastName,
        email: this.email,
        password: this.password,
      })
      .subscribe({
        next: () => {
          this.cargando = false;
          this.successMsg = 'Registro exitoso. Ahora podés iniciar sesión.';

          setTimeout(() => {
            this.router.navigate(['/auth/login'], {
              queryParams: { email: this.email },
            });
          }, 1200);
        },
        error: () => {
          this.cargando = false;
          this.errorMsg = 'No se pudo completar el registro.';
        },
      });
  }
}
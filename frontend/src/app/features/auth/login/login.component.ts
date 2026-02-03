import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css'],
})
export class LoginComponent implements OnInit {
  email = '';
  password = '';
  cargando = false;
  errorMsg = '';

  private redirectUrl: string | null = null;

  constructor(
    private auth: AuthService,
    private router: Router,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.redirectUrl = this.route.snapshot.queryParamMap.get('redirectUrl');
  }

  submit() {
    this.errorMsg = '';

    if (!this.email || !this.password) {
      this.errorMsg = 'Completá email y contraseña.';
      return;
    }

    this.cargando = true;

    this.auth.login(this.email, this.password).subscribe({
      next: () => {
        this.cargando = false;

        // 1️⃣ Si venía de una ruta protegida (admin o user), respetamos eso
        if (this.redirectUrl && this.redirectUrl.startsWith('/')) {
          this.router.navigateByUrl(this.redirectUrl);
          return;
        }

        // 2️⃣ Si no, redirigimos según rol
        if (this.auth.isAdmin()) {
          this.router.navigateByUrl('/admin');
        } else {
          this.router.navigateByUrl('/cotizar');
        }
      },
      error: (err) => {
        this.cargando = false;
        this.errorMsg =
          err?.status === 401
            ? 'Credenciales inválidas.'
            : 'No se pudo iniciar sesión.';
      },
    });
  }
}
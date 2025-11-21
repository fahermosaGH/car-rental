import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css'],
})
export class LoginComponent implements OnInit {
  email = '';
  password = '';
  cargando = false;
  errorMsg = '';

  /** adonde volver luego de loguear (pasa desde detalle → /auth/login?redirectUrl=...) */
  private redirectUrl: string | null = null;

  constructor(
    private auth: AuthService,
    private router: Router,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    // lee el redirectUrl si vino en la query
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

        // Si vino redirectUrl y es una ruta interna, volvemos ahí; si no, a /cotizar
        const target =
          this.redirectUrl && this.redirectUrl.startsWith('/')
            ? this.redirectUrl
            : '/cotizar';

        this.router.navigateByUrl(target);
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


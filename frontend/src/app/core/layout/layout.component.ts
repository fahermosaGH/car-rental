import { Component, HostBinding } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { AuthService, AuthUser } from '../services/auth.service';
import { Observable } from 'rxjs';

@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './layout.component.html',
  styleUrls: ['./layout.component.css'],
})
export class LayoutComponent {
  currentYear = new Date().getFullYear();

  // usuario actual (email + roles)
  user$: Observable<AuthUser | null>;

  // 🔦 flag + clase en el host para modo oscuro
  @HostBinding('class.dark-theme') darkMode = false;

  constructor(private auth: AuthService, private router: Router) {
    this.user$ = this.auth.user$;

    // si querés que recuerde el tema entre recargas:
    const saved = localStorage.getItem('theme');
    if (saved === 'dark') {
      this.darkMode = true;
    }
  }

  get isLoggedIn(): boolean {
    return this.auth.isLoggedIn();
  }

  // ✅ Solo muestra "Panel Admin" si tiene ROLE_ADMIN
  get isAdmin(): boolean {
    return this.auth.isAdmin();
  }

  logout() {
    const ok = window.confirm('¿Estás seguro de que querés cerrar sesión?');
    if (!ok) return;

    this.auth.logout();

    // ✅ Cambiado: después de cerrar sesión, ir al login (sin tocar permisos/guards)
    this.router.navigate(['/auth/login']);
  }

  // 🔘 toggle del modo oscuro
  toggleDark(): void {
    this.darkMode = !this.darkMode;
    localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
  }
}
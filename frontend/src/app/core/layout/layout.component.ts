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

  logout(): void {
    this.auth.logout();
    this.router.navigate(['/cotizar']);
  }

  // 🔘 toggle del modo oscuro
  toggleDark(): void {
    this.darkMode = !this.darkMode;
    localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
  }
}

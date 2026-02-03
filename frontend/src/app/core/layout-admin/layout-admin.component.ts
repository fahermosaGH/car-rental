import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-layout-admin',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './layout-admin.component.html',
  styleUrls: ['./layout-admin.component.css'],
})
export class LayoutAdminComponent {
  get email(): string {
    return this.auth.currentUser?.email ?? '';
  }

  constructor(private auth: AuthService, private router: Router) {}

  logout(): void {
    this.auth.logout();
    this.router.navigate(['/auth/login']);
  }
}

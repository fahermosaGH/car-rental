import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AdminStatsService, AdminGeneralStats } from '../services/admin-stats.service';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './admin-dashboard.component.html',
  styleUrls: ['./admin-dashboard.component.css'],
})
export class AdminDashboardComponent implements OnInit {
  loading = true;
  error = '';
  stats: AdminGeneralStats | null = null;

  constructor(private adminStats: AdminStatsService) {}

  ngOnInit(): void {
    this.cargar();
  }

  cargar(): void {
    this.loading = true;
    this.error = '';

    this.adminStats.getGeneralStats().subscribe({
      next: (data) => {
        this.stats = data;
        this.loading = false;
      },
      error: () => {
        this.error = 'No se pudieron cargar las métricas del admin.';
        this.stats = null;
        this.loading = false;
      },
    });
  }

  maxValue(list: { value: number }[] | undefined | null): number {
    if (!list || list.length === 0) return 0;
    return Math.max(...list.map((x) => x.value));
  }

  barWidth(value: number, max: number): string {
    if (!max) return '0%';
    return `${Math.round((value / max) * 100)}%`;
  }
}

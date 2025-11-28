import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpClientModule } from '@angular/common/http';

interface VehicleDto {
  id: number;
  brand: string;
  model: string;
  year: number | null;
  seats: number | null;
  transmission: string | null;
  dailyRate: string | null;
  isActive: boolean;
  category: string | null;
}

// 👇 apuntamos directo al backend Symfony
const API_BASE = 'http://localhost:8000/api';

@Component({
  selector: 'app-ver-flota',
  standalone: true,
  imports: [CommonModule, HttpClientModule],
  templateUrl: './ver-flota.component.html',
  styleUrls: ['./ver-flota.component.css'],
})
export class VerFlotaComponent implements OnInit {
  private http = inject(HttpClient);

  loading = false;
  error: string | null = null;
  vehicles: VehicleDto[] = [];

  ngOnInit(): void {
    this.loadVehicles();
  }

  loadVehicles(): void {
    this.loading = true;
    this.error = null;

    this.http.get<VehicleDto[]>(`${API_BASE}/vehicles`).subscribe({
      next: (data) => {
        this.vehicles = data || [];
        this.loading = false;
      },
      error: (err) => {
        console.error('Error al cargar la flota', err);
        this.error = 'No se pudo cargar la flota de vehículos.';
        this.loading = false;
      },
    });
  }
}


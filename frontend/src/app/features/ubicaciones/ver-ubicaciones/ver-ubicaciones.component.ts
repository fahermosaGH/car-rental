import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpClientModule } from '@angular/common/http';

interface LocationDto {
  id: number;
  name: string;
  address: string;
}

const API_BASE = 'http://localhost:8000/api';

@Component({
  selector: 'app-ver-ubicaciones',
  standalone: true,
  imports: [CommonModule, HttpClientModule],
  templateUrl: './ver-ubicaciones.component.html',
  styleUrls: ['./ver-ubicaciones.component.css'],
})
export class VerUbicacionesComponent implements OnInit {
  private http = inject(HttpClient);

  loading = false;
  error: string | null = null;
  locations: LocationDto[] = [];

  ngOnInit(): void {
    this.loadLocations();
  }

  loadLocations(): void {
    this.loading = true;
    this.error = null;

    this.http.get<LocationDto[]>(`${API_BASE}/locations`).subscribe({
      next: (data) => {
        this.locations = data || [];
        this.loading = false;
      },
      error: (err) => {
        console.error('Error al cargar las ubicaciones', err);
        this.error = 'No se pudieron cargar las ubicaciones.';
        this.loading = false;
      },
    });
  }
}


import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpClientModule } from '@angular/common/http';
import { FormsModule } from '@angular/forms';

interface LocationDto {
  id: number;
  name: string;
  address: string;
}

const API_BASE = 'http://localhost:8000/api';

@Component({
  selector: 'app-ver-ubicaciones',
  standalone: true,
  imports: [CommonModule, HttpClientModule, FormsModule],
  templateUrl: './ver-ubicaciones.component.html',
  styleUrls: ['./ver-ubicaciones.component.css']
})
export class VerUbicacionesComponent implements OnInit {

  private http = inject(HttpClient);

  loading = false;
  error: string | null = null;

  locations: LocationDto[] = [];
  filtered: LocationDto[] = [];

  searchText: string = '';   // mientras escribís
  searchTrigger: string = ''; // se activa SOLO cuando apretás ENTER

  ngOnInit(): void {
    this.loadLocations();
  }

  loadLocations(): void {
    this.loading = true;
    this.error = null;

    this.http.get<LocationDto[]>(`${API_BASE}/locations`).subscribe({
      next: (data) => {
        this.locations = data || [];
        this.applyFilters();
        this.loading = false;
      },
      error: () => {
        this.error = 'No se pudieron cargar las ubicaciones.';
        this.loading = false;
      }
    });
  }

  onSearchEnter() {
    this.searchTrigger = this.searchText.trim().toLowerCase();
    this.applyFilters();
  }

  applyFilters() {
    let items = [...this.locations];

    if (this.searchTrigger !== '') {
      items = items.filter(loc =>
        loc.name.toLowerCase().includes(this.searchTrigger) ||
        loc.address.toLowerCase().includes(this.searchTrigger)
      );
    }

    this.filtered = items;
  }

  openInMaps(loc: LocationDto) {
    const query = encodeURIComponent(`${loc.name}, ${loc.address}`);
    window.open(`https://www.google.com/maps/search/?api=1&query=${query}`, '_blank');
  }
} 
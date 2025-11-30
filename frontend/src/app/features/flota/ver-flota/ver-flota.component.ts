import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpClientModule } from '@angular/common/http';
import { FormsModule } from '@angular/forms';

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
  img?: string | null;
}

const API_BASE = 'http://localhost:8000/api';

@Component({
  selector: 'app-ver-flota',
  standalone: true,
  imports: [CommonModule, HttpClientModule, FormsModule],
  templateUrl: './ver-flota.component.html',
  styleUrls: ['./ver-flota.component.css'],
})
export class VerFlotaComponent implements OnInit {
  private http = inject(HttpClient);

  loading = false;
  loadingFilter = false;

  error: string | null = null;
  vehicles: VehicleDto[] = [];
  filtered: VehicleDto[] = [];

  categories = ['Todos', 'Económico', 'Compacto', 'SUV', 'Camioneta', 'Sedán', 'Premium', 'Largos'];
  selectedCategory = 'Todos';

  sort: 'az' | 'za' = 'az';

  // texto del buscador (pero NO filtra hasta apretar ENTER)
  searchText: string = '';
  searchTrigger: string = ''; // 👈 se usa para filtrar al presionar ENTER

  ngOnInit(): void {
    this.loadVehicles();
  }

  loadVehicles(): void {
    this.loading = true;
    this.error = null;

    this.http.get<VehicleDto[]>(`${API_BASE}/vehicles`).subscribe({
      next: (data) => {
        this.vehicles = data || [];
        this.applyFilters(false);
        this.loading = false;
      },
      error: () => {
        this.error = 'No se pudo cargar la flota de vehículos.';
        this.loading = false;
      },
    });
  }

  selectCategory(cat: string) {
    this.selectedCategory = cat;
    this.applyFilters(true);
  }

  changeSort(dir: 'az' | 'za') {
    this.sort = dir;
    this.applyFilters(true);
  }

  // SOLO FILTRA AL APRETAR ENTER
  onSearchEnter() {
    this.searchTrigger = this.searchText.trim().toLowerCase();
    this.applyFilters(true);
  }

  applyFilters(withAnimation: boolean = true) {
    if (withAnimation) {
      this.loadingFilter = true;
      setTimeout(() => {
        this.runFiltering();
        this.loadingFilter = false;
      }, 400);
    } else {
      this.runFiltering();
    }
  }

  private runFiltering() {
    let items = [...this.vehicles];

    // CATEGORÍA
    if (this.selectedCategory !== 'Todos') {
      items = items.filter(v =>
        (v.category || '').toLowerCase() === this.selectedCategory.toLowerCase()
      );
    }

    // BUSCADOR (ACTÚA SOLO AL APRETAR ENTER)
    if (this.searchTrigger !== '') {
      items = items.filter(v =>
        `${v.brand} ${v.model}`.toLowerCase().includes(this.searchTrigger) ||
        (v.category || '').toLowerCase().includes(this.searchTrigger)
      );
    }

    // ORDEN
    if (this.sort === 'az') {
      items.sort((a, b) => (a.brand + a.model).localeCompare(b.brand + b.model));
    } else {
      items.sort((a, b) => (b.brand + b.model).localeCompare(a.brand + a.model));
    }

    this.filtered = items;
  }
}
export interface VehicleOption {
  id: number;
  category: string;

  // datos del vehículo
  brand: string;
  model: string;
  name: string; // brand + model
  year?: number;
  seats?: number;
  transmission?: string;

  // economía
  dailyRate: number;
  description?: string;
  fuel?: string;

  // imágenes
  img: string;

  // disponibilidad real
  unitsAvailable?: number;
  branchStock?: number;
  taken?: number;
}

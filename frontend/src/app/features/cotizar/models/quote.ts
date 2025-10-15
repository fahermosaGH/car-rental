export interface VehicleOption {
  id: number;
  category: string;
  name: string;
  dailyRate: number;
  img: string;
  description?: string;
  transmission?: string;
  fuel?: string;
}

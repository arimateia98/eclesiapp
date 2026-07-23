export interface ApiEnvelope<T> {
  data: T
}

export interface HealthData {
  service: string
  status: 'ok'
  timestamp: string
}

import axios from 'axios';

// Base URL points to /api (proxied by Vite or direct to port 8000)
const API_BASE = import.meta.env.VITE_API_BASE_URL || '/api';

export const apiClient = axios.create({
  baseURL: API_BASE,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Attach token if present in localStorage
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('goers_admin_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export const RestaurantAPI = {
  list: async (params = {}) => {
    const response = await apiClient.get('/restaurants', { params });
    return response.data;
  },

  get: async (id) => {
    const response = await apiClient.get(`/restaurants/${id}`);
    return response.data;
  },

  create: async (data) => {
    const response = await apiClient.post('/restaurants', data);
    return response.data;
  },

  update: async (id, data) => {
    const response = await apiClient.put(`/restaurants/${id}`, data);
    return response.data;
  },

  delete: async (id) => {
    const response = await apiClient.delete(`/restaurants/${id}`);
    return response.data;
  },

  stats: async () => {
    const response = await apiClient.get('/stats');
    return response.data;
  },
};

export const AuthAPI = {
  login: async (email, password) => {
    const response = await apiClient.post('/auth/login', { email, password });
    if (response.data?.token) {
      localStorage.setItem('goers_admin_token', response.data.token);
      localStorage.setItem('goers_admin_user', JSON.stringify(response.data.user));
    }
    return response.data;
  },

  logout: async () => {
    try {
      await apiClient.post('/auth/logout');
    } finally {
      localStorage.removeItem('goers_admin_token');
      localStorage.removeItem('goers_admin_user');
    }
  },

  getCurrentUser: () => {
    const raw = localStorage.getItem('goers_admin_user');
    return raw ? JSON.parse(raw) : null;
  },
};

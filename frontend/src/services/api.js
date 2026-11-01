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

const TOKEN_KEY = 'goers_admin_token';
const USER_KEY = 'goers_admin_user';

// Attach token if present in localStorage
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY);
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// A stale or revoked token must never look like a broken index.
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && localStorage.getItem(TOKEN_KEY)) {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(USER_KEY);
      window.dispatchEvent(new CustomEvent('goers:unauthorized'));
    }
    return Promise.reject(error);
  }
);

export function isCanceledRequest(error) {
  return (
    axios.isCancel(error) ||
    error?.code === 'ERR_CANCELED' ||
    error?.name === 'CanceledError'
  );
}

/**
 * Turn an axios failure into a message a human can act on.
 */
export function describeApiError(error, fallback = 'The request could not be completed.') {
  if (!error?.response) {
    return {
      title: 'The Laravel API is not reachable.',
      detail:
        'Start the backend with `php artisan serve --port=8000` and make sure it is still running, then retry.',
      status: null,
    };
  }

  const { status, data } = error.response;
  const serverMessage = data?.message || data?.error;

  // The Vite proxy answers 502/503/504 when the backend on :8000 is stopped.
  if ([502, 503, 504].includes(status)) {
    return {
      title: 'The Laravel API is not reachable.',
      detail:
        'The dev proxy could not reach the backend on port 8000. Start it with `php artisan serve --port=8000`, then retry.',
      status,
    };
  }

  if (status === 401) {
    return {
      title: 'Admin sign-in required.',
      detail: serverMessage || 'Your session expired. Sign in again to manage restaurant records.',
      status,
    };
  }

  if (status === 404) {
    return {
      title: 'The endpoint was not found.',
      detail: serverMessage || 'Check that the frontend proxy points at the Laravel API.',
      status,
    };
  }

  if (status === 422) {
    return {
      title: 'The request was rejected.',
      detail: serverMessage || 'Some fields are invalid. Review the form and submit again.',
      status,
    };
  }

  if (status >= 500) {
    return {
      title: `The API failed with status ${status}.`,
      detail:
        serverMessage ||
        'The server threw an error. Check backend/storage/logs/laravel.log for the stack trace.',
      status,
    };
  }

  return {
    title: fallback,
    detail: serverMessage || `The API answered with status ${status}.`,
    status,
  };
}

export const RestaurantAPI = {
  list: async (params = {}, signal) => {
    const response = await apiClient.get('/restaurants', { params, signal });
    return response.data;
  },

  get: async (id, signal) => {
    const response = await apiClient.get(`/restaurants/${id}`, { signal });
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

  stats: async (signal) => {
    const response = await apiClient.get('/stats', { signal });
    return response.data;
  },
};

export const AuthAPI = {
  login: async (email, password) => {
    const response = await apiClient.post('/auth/login', { email, password });
    if (response.data?.token) {
      localStorage.setItem(TOKEN_KEY, response.data.token);
      localStorage.setItem(USER_KEY, JSON.stringify(response.data.user));
    }
    return response.data;
  },

  logout: async () => {
    try {
      await apiClient.post('/auth/logout');
    } finally {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(USER_KEY);
    }
  },

  getCurrentUser: () => {
    const raw = localStorage.getItem(USER_KEY);
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch {
      localStorage.removeItem(USER_KEY);
      localStorage.removeItem(TOKEN_KEY);
      return null;
    }
  },
};

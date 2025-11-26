// resources/js/Services/ApiService.js
import axios from 'axios';
import { toast } from 'vue3-toastify';

const instance = axios.create({
  baseURL: '/api',
});

instance.interceptors.request.use((config) => {
  const apiKey = window.localStorage.getItem('taskflow_api_key');
  if (apiKey) {
    config.headers['X-API-Key'] = apiKey;
  }
  return config;
});

instance.interceptors.response.use(
  (response) => response,
  (error) => {
    toast.error('There was a problem talking to the server. Try again in a moment.');
    return Promise.reject(error);
  }
);

export default {
  async get(url, params = {}) {
    const { data } = await instance.get(url, { params });
    if (data.success === false && data.error) {
      toast.error(data.error);
    }
    return data;
  },

  async post(url, payload = {}) {
    const { data } = await instance.post(url, payload);
    if (data.success === false && data.error) {
      toast.error(data.error);
    }
    return data;
  },

  async patch(url, payload = {}) {
    const { data } = await instance.patch(url, payload);
    if (data.success === false && data.error) {
      toast.error(data.error);
    }
    return data;
  },

  async delete(url) {
    const { data } = await instance.delete(url);
    if (data.success === false && data.error) {
      toast.error(data.error);
    }
    return data;
  },
};

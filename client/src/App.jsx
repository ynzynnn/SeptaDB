import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { AuthProvider } from './context/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';

// Auth Pages
import Login from './pages/auth/Login';

// User Pages
import UserDashboard from './pages/user/Dashboard';
import UserDatabases from './pages/user/Databases';
import UserProfile from './pages/user/Profile';

// Admin Pages
import AdminDashboard from './pages/admin/Dashboard';
import AdminDatabases from './pages/admin/Databases';
import AdminUsers from './pages/admin/Users';
import AdminApiKeys from './pages/admin/ApiKeys';
import AdminSettings from './pages/admin/Settings';

export const App = () => {
  return (
    <AuthProvider>
      <Toaster
        position="top-right"
        toastOptions={{
          style: {
            background: '#ffffff',
            color: '#09090b',
            border: '1px solid #e4e4e7',
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.08)',
            fontSize: '13px',
            fontFamily: 'var(--font-sans)',
            borderRadius: '4px',
          },
          success: {
            iconTheme: {
              primary: '#166534',
              secondary: '#fff',
            },
          },
          error: {
            iconTheme: {
              primary: '#991b1b',
              secondary: '#fff',
            },
          },
        }}
      />

      <BrowserRouter>
        <Routes>
          {/* Public Authentication */}
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Navigate to="/login" replace />} />

          {/* User Protected Routes */}
          <Route element={<ProtectedRoute adminOnly={false} />}>
            <Route element={<Layout />}>
              <Route path="/" element={<Navigate to="/user/dashboard" replace />} />
              <Route path="/user/dashboard" element={<UserDashboard />} />
              <Route path="/user/databases" element={<UserDatabases />} />
              <Route path="/user/profile" element={<UserProfile />} />
            </Route>
          </Route>

          {/* Admin Protected Routes */}
          <Route element={<ProtectedRoute adminOnly={true} />}>
            <Route element={<Layout />}>
              <Route path="/admin" element={<Navigate to="/admin/dashboard" replace />} />
              <Route path="/admin/dashboard" element={<AdminDashboard />} />
              <Route path="/admin/databases" element={<AdminDatabases />} />
              <Route path="/admin/users" element={<AdminUsers />} />
              <Route path="/admin/api-keys" element={<AdminApiKeys />} />
              <Route path="/admin/settings" element={<AdminSettings />} />
            </Route>
          </Route>

          {/* Fallback */}
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
};

export default App;

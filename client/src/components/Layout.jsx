import React from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import Sidebar from './Sidebar';
import Topbar from './Topbar';

export const Layout = () => {
  const location = useLocation();

  const getPageTitle = (path) => {
    if (path.includes('/user/dashboard')) return 'Overview';
    if (path.includes('/user/databases')) return 'Databases';
    if (path.includes('/user/profile')) return 'Account Settings';
    if (path.includes('/admin/dashboard')) return 'System Overview';
    if (path.includes('/admin/databases')) return 'Database Instances';
    if (path.includes('/admin/users')) return 'User Directory';
    if (path.includes('/admin/api-keys')) return 'Paymenter API Integrations';
    if (path.includes('/admin/settings')) return 'System Configuration';
    return 'Dashboard';
  };

  return (
    <div className="app-container">
      <Sidebar />
      <div className="main-wrapper">
        <Topbar title={getPageTitle(location.pathname)} />
        <main className="content-area">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default Layout;

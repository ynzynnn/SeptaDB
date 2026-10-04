import React from 'react';
import { NavLink, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import {
  Database,
  LayoutDashboard,
  Server,
  Users,
  Key,
  Settings,
  User as UserIcon,
  LogOut,
  Shield
} from 'lucide-react';

export const Sidebar = () => {
  const { user, isAdmin, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const isAdminSection = location.pathname.startsWith('/admin');

  return (
    <aside className="sidebar">
      <div className="sidebar-header">
        <div className="brand-badge">
          <Database size={16} />
        </div>
        <div>
          <div className="brand-title">SeptaDB</div>
          <div className="brand-subtitle">Database Manager</div>
        </div>
      </div>

      <div className="sidebar-nav">
        {/* Toggle between Admin Mode and Client Mode if Admin */}
        {isAdmin && (
          <div style={{ padding: '2px 4px', marginBottom: '8px' }}>
            <div style={{
              display: 'flex',
              background: '#f4f4f5',
              borderRadius: '4px',
              padding: '2px',
              border: '1px solid var(--border-color)'
            }}>
              <button
                type="button"
                onClick={() => navigate('/user/dashboard')}
                style={{
                  flex: 1,
                  padding: '5px',
                  borderRadius: '3px',
                  fontSize: '11px',
                  fontWeight: 600,
                  color: !isAdminSection ? '#ffffff' : 'var(--text-muted)',
                  background: !isAdminSection ? 'var(--primary)' : 'transparent',
                  textAlign: 'center'
                }}
              >
                Client View
              </button>
              <button
                type="button"
                onClick={() => navigate('/admin/dashboard')}
                style={{
                  flex: 1,
                  padding: '5px',
                  borderRadius: '3px',
                  fontSize: '11px',
                  fontWeight: 600,
                  color: isAdminSection ? '#ffffff' : 'var(--text-muted)',
                  background: isAdminSection ? 'var(--primary)' : 'transparent',
                  textAlign: 'center'
                }}
              >
                Admin Panel
              </button>
            </div>
          </div>
        )}

        {/* Section 1: Client Panel */}
        {(!isAdmin || !isAdminSection) && (
          <>
            <div className="nav-section-title">Client Panel</div>

            <NavLink to="/user/dashboard" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              <LayoutDashboard size={16} />
              <span>Dashboard</span>
            </NavLink>

            <NavLink to="/user/databases" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              <Server size={16} />
              <span>Databases</span>
            </NavLink>

            <NavLink to="/user/profile" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              <UserIcon size={16} />
              <span>Account & Security</span>
            </NavLink>
          </>
        )}

        {/* Section 2: Admin Panel */}
        {isAdmin && isAdminSection && (
          <>
            <div className="nav-section-title">Administration</div>

            <NavLink to="/admin/dashboard" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              <LayoutDashboard size={16} />
              <span>Overview</span>
            </NavLink>

            <NavLink to="/admin/databases" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              <Server size={16} />
              <span>All Databases</span>
            </NavLink>

            <NavLink to="/admin/users" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              <Users size={16} />
              <span>User Accounts</span>
            </NavLink>

            <div className="nav-section-title">Integrations & System</div>

            <NavLink to="/admin/api-keys" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              <Key size={16} />
              <span>Paymenter API Keys</span>
            </NavLink>

            <NavLink to="/admin/settings" className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}>
              <Settings size={16} />
              <span>Panel Settings</span>
            </NavLink>
          </>
        )}
      </div>

      <div className="sidebar-footer">
        <div className="user-snippet">
          <div className="avatar">
            {user?.name?.charAt(0)?.toUpperCase() || 'U'}
          </div>
          <div>
            <div className="user-meta-name">{user?.username || user?.name}</div>
            <div className="user-meta-role">
              {user?.role === 'admin' ? (
                <span style={{ color: 'var(--text-main)', display: 'flex', alignItems: 'center', gap: '3px', fontWeight: 600 }}>
                  <Shield size={11} /> Admin
                </span>
              ) : 'Client'}
            </div>
          </div>
        </div>

        <button
          onClick={handleLogout}
          title="Sign Out"
          style={{ color: 'var(--text-dim)', padding: '6px' }}
        >
          <LogOut size={16} />
        </button>
      </div>
    </aside>
  );
};

export default Sidebar;

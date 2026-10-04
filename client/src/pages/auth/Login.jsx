import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { Database, Lock, User, ArrowRight, ShieldCheck } from 'lucide-react';
import toast from 'react-hot-toast';

export const Login = () => {
  const [loginInput, setLoginInput] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!loginInput || !password) {
      toast.error('Please enter your username/email and password.');
      return;
    }

    setLoading(true);
    try {
      const user = await login(loginInput, password);
      toast.success(`Welcome back, ${user.name}!`);
      if (user.role === 'admin') {
        navigate('/admin/dashboard');
      } else {
        navigate('/user/dashboard');
      }
    } catch (err) {
      const msg = err.response?.data?.message || err.response?.data?.errors?.login?.[0] || 'Login failed. Please check credentials.';
      toast.error(msg);
    } finally {
      setLoading(false);
    }
  };

  const setDemoCredentials = (role) => {
    if (role === 'admin') {
      setLoginInput('admin');
      setPassword('admin123');
    } else {
      setLoginInput('johndoe');
      setPassword('user123');
    }
    toast.success(`Loaded ${role} credentials`);
  };

  return (
    <div className="auth-wrapper">
      <div className="auth-card">
        <div className="auth-header">
          <div className="brand-badge" style={{ margin: '0 auto 12px', width: '42px', height: '42px' }}>
            <Database size={24} />
          </div>
          <h1>NexusDB Panel</h1>
          <p>Database Hosting & Provisioning Management</p>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label className="form-label">Username or Email</label>
            <div style={{ position: 'relative' }}>
              <input
                type="text"
                className="form-control"
                placeholder="admin or user@domain.com"
                value={loginInput}
                onChange={(e) => setLoginInput(e.target.value)}
                required
              />
            </div>
          </div>

          <div className="form-group">
            <label className="form-label">Password</label>
            <input
              type="password"
              className="form-control"
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>

          <button
            type="submit"
            className="btn btn-primary"
            style={{ width: '100%', marginTop: '8px' }}
            disabled={loading}
          >
            {loading ? 'Authenticating...' : 'Sign In'}
          </button>
        </form>

        {/* Quick Demo Fill Buttons for convenience */}
        <div style={{
          marginTop: '20px',
          padding: '12px',
          background: '#fafafa',
          borderRadius: '6px',
          border: '1px solid var(--border-color)',
          fontSize: '12px'
        }}>
          <div style={{ color: 'var(--text-muted)', marginBottom: '8px', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '5px' }}>
            <ShieldCheck size={14} color="var(--text-main)" /> Quick Login Demo Accounts:
          </div>
          <div style={{ display: 'flex', gap: '8px' }}>
            <button
              type="button"
              onClick={() => setDemoCredentials('admin')}
              className="btn btn-secondary btn-sm"
              style={{ flex: 1, fontSize: '11px' }}
            >
              Fill Admin (admin)
            </button>
            <button
              type="button"
              onClick={() => setDemoCredentials('user')}
              className="btn btn-secondary btn-sm"
              style={{ flex: 1, fontSize: '11px' }}
            >
              Fill User (johndoe)
            </button>
          </div>
        </div>

        <div style={{ textAlign: 'center', marginTop: '18px', fontSize: '12.5px', color: 'var(--text-muted)' }}>
          Don't have an account?{' '}
          <Link to="/register" style={{ color: 'var(--primary)', fontWeight: 600 }}>
            Create Account
          </Link>
        </div>
      </div>
    </div>
  );
};

export default Login;

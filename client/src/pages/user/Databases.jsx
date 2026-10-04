import React, { useEffect, useState } from 'react';
import api from '../../api/client';
import {
  Server,
  Plus,
  Key,
  Copy,
  ExternalLink,
  Eye,
  EyeOff,
  RefreshCw,
  Trash2,
  X
} from 'lucide-react';
import toast from 'react-hot-toast';

export const UserDatabases = () => {
  const [databases, setDatabases] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedDb, setSelectedDb] = useState(null);
  const [showPassword, setShowPassword] = useState(false);
  const [createModal, setCreateModal] = useState(false);
  const [creating, setCreating] = useState(false);
  const [resetting, setResetting] = useState(false);

  const [createForm, setCreateForm] = useState({
    name: '',
    type: 'mysql',
    database_name: '',
  });

  useEffect(() => {
    fetchDatabases();
  }, []);

  const fetchDatabases = async () => {
    try {
      const res = await api.get('/user/databases');
      setDatabases(res.data.databases);
    } catch (err) {
      toast.error('Failed to load databases.');
    } finally {
      setLoading(false);
    }
  };

  const handleCreate = async (e) => {
    e.preventDefault();
    setCreating(true);
    try {
      await api.post('/user/databases', createForm);
      toast.success('Database created successfully.');
      setCreateModal(false);
      setCreateForm({ name: '', type: 'mysql', database_name: '' });
      fetchDatabases();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to create database.');
    } finally {
      setCreating(false);
    }
  };

  const handleDelete = async (id, name) => {
    if (!window.confirm(`Delete database "${name}"? All data will be permanently dropped.`)) return;

    try {
      await api.delete(`/user/databases/${id}`);
      toast.success('Database deleted.');
      if (selectedDb && selectedDb.id === id) {
        setSelectedDb(null);
      }
      fetchDatabases();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to delete database.');
    }
  };

  const handleResetPassword = async (dbId) => {
    if (!window.confirm('Reset database password? Active applications using the previous password will disconnect until updated.')) {
      return;
    }

    setResetting(true);
    try {
      const res = await api.post(`/user/databases/${dbId}/reset-password`);
      toast.success('Password successfully reset.');
      
      setDatabases(prev => prev.map(item => {
        if (item.id === dbId) {
          return {
            ...item,
            password: res.data.new_password,
            connection_string: res.data.connection_string
          };
        }
        return item;
      }));

      if (selectedDb && selectedDb.id === dbId) {
        setSelectedDb(prev => ({
          ...prev,
          password: res.data.new_password,
          connection_string: res.data.connection_string
        }));
      }
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to reset password');
    } finally {
      setResetting(false);
    }
  };

  const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text);
    toast.success(`${label} copied!`);
  };

  if (loading) {
    return <div style={{ color: 'var(--text-muted)', padding: '20px' }}>Loading databases...</div>;
  }

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <div>
          <h2 style={{ fontSize: '16px', fontWeight: 600 }}>Databases</h2>
          <p style={{ fontSize: '12.5px', color: 'var(--text-muted)' }}>
            Manage database server instances, credentials, and connection endpoints
          </p>
        </div>
        <button onClick={() => setCreateModal(true)} className="btn btn-primary">
          <Plus size={15} /> Create Database
        </button>
      </div>

      {databases.length === 0 ? (
        <div className="card" style={{ textAlign: 'center', padding: '40px 20px' }}>
          <Server size={38} style={{ margin: '0 auto 10px', opacity: 0.3 }} />
          <h3 style={{ fontSize: '15px', fontWeight: 600, marginBottom: '4px' }}>No Databases</h3>
          <p style={{ color: 'var(--text-muted)', fontSize: '12.5px', maxWidth: '380px', margin: '0 auto 16px' }}>
            You have not created any databases yet. Create an instance to get credentials and endpoints.
          </p>
          <button onClick={() => setCreateModal(true)} className="btn btn-primary btn-sm">
            Create Database
          </button>
        </div>
      ) : (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(330px, 1fr))', gap: '14px' }}>
          {databases.map((db) => (
            <div key={db.id} className="card" style={{ margin: 0, display: 'flex', flexDirection: 'column' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '10px' }}>
                <div>
                  <h3 style={{ fontSize: '14px', fontWeight: 600 }}>{db.name}</h3>
                  <div style={{ fontSize: '11px', color: 'var(--text-muted)', textTransform: 'uppercase', marginTop: '2px' }}>
                    Engine: <strong style={{ color: 'var(--text-main)' }}>{db.type}</strong>
                  </div>
                </div>
                <span className={`badge ${db.status === 'active' ? 'badge-success' : 'badge-danger'}`}>
                  {db.status}
                </span>
              </div>

              {/* Connection Details */}
              <div style={{ fontSize: '12.5px', display: 'flex', flexDirection: 'column', gap: '5px', marginBottom: '12px', flex: 1 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span style={{ color: 'var(--text-muted)' }}>Endpoint:</span>
                  <span style={{ fontFamily: 'var(--font-mono)' }}>{db.host}:{db.port}</span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span style={{ color: 'var(--text-muted)' }}>Database:</span>
                  <span style={{ fontFamily: 'var(--font-mono)', color: 'var(--text-main)' }}>{db.database_name}</span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span style={{ color: 'var(--text-muted)' }}>Username:</span>
                  <span style={{ fontFamily: 'var(--font-mono)', color: 'var(--text-main)' }}>{db.database_username}</span>
                </div>
              </div>

              {/* Connection String */}
              <div className="mono-box" style={{ marginBottom: '12px' }}>
                <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                  {db.connection_string}
                </span>
                <button
                  onClick={() => copyToClipboard(db.connection_string, 'Connection String')}
                  title="Copy Connection String"
                  style={{ color: 'var(--text-main)' }}
                >
                  <Copy size={13} />
                </button>
              </div>

              {/* Action Buttons */}
              <div style={{ display: 'flex', gap: '6px' }}>
                <button
                  onClick={() => {
                    setSelectedDb(db);
                    setShowPassword(false);
                  }}
                  className="btn btn-secondary btn-sm"
                  style={{ flex: 1 }}
                >
                  <Key size={13} /> Credentials
                </button>

                {db.phpmyadmin_url && (
                  <a
                    href={db.phpmyadmin_url}
                    target="_blank"
                    rel="noreferrer"
                    className="btn btn-secondary btn-sm"
                    title="Open in phpMyAdmin"
                  >
                    <ExternalLink size={13} />
                  </a>
                )}

                <button
                  onClick={() => handleDelete(db.id, db.name)}
                  className="btn btn-danger btn-sm"
                  title="Delete Database"
                >
                  <Trash2 size={13} />
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Create Database Modal */}
      {createModal && (
        <div className="modal-overlay" onClick={() => setCreateModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3 className="modal-title">Create Database Instance</h3>
              <button onClick={() => setCreateModal(false)} style={{ color: 'var(--text-muted)' }}>
                <X size={16} />
              </button>
            </div>

            <form onSubmit={handleCreate}>
              <div className="form-group">
                <label className="form-label">Instance Label</label>
                <input
                  type="text"
                  className="form-control"
                  placeholder="e.g. Production Application DB"
                  value={createForm.name}
                  onChange={(e) => setCreateForm({ ...createForm, name: e.target.value })}
                  required
                />
              </div>

              <div className="form-group">
                <label className="form-label">Database Engine</label>
                <select
                  className="form-control"
                  value={createForm.type}
                  onChange={(e) => setCreateForm({ ...createForm, type: e.target.value })}
                >
                  <option value="mysql">MySQL 8.4</option>
                  <option value="mariadb">MariaDB 11</option>
                  <option value="postgresql">PostgreSQL 16</option>
                  <option value="mongodb">MongoDB 7</option>
                  <option value="redis">Redis Cache</option>
                </select>
              </div>

              <div className="form-group">
                <label className="form-label">Database Name (Optional)</label>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Leave empty for auto-generation"
                  value={createForm.database_name}
                  onChange={(e) => setCreateForm({ ...createForm, database_name: e.target.value })}
                />
              </div>

              <div className="modal-footer">
                <button type="button" className="btn btn-secondary" onClick={() => setCreateModal(false)}>
                  Cancel
                </button>
                <button type="submit" className="btn btn-primary" disabled={creating}>
                  {creating ? 'Creating...' : 'Create Database'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Credentials Modal */}
      {selectedDb && (
        <div className="modal-overlay" onClick={() => setSelectedDb(null)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3 className="modal-title">Database Credentials</h3>
              <button onClick={() => setSelectedDb(null)} style={{ color: 'var(--text-muted)' }}>
                <X size={16} />
              </button>
            </div>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div>
                <label className="form-label">Instance Name</label>
                <input type="text" className="form-control" value={selectedDb.name} readOnly />
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: '10px' }}>
                <div>
                  <label className="form-label">Host</label>
                  <div style={{ display: 'flex', gap: '6px' }}>
                    <input type="text" className="form-control" style={{ fontFamily: 'var(--font-mono)' }} value={selectedDb.host} readOnly />
                    <button className="btn btn-secondary btn-sm" onClick={() => copyToClipboard(selectedDb.host, 'Host')}>
                      <Copy size={12} />
                    </button>
                  </div>
                </div>
                <div>
                  <label className="form-label">Port</label>
                  <input type="text" className="form-control" style={{ fontFamily: 'var(--font-mono)' }} value={selectedDb.port} readOnly />
                </div>
              </div>

              <div>
                <label className="form-label">Database Name</label>
                <div style={{ display: 'flex', gap: '6px' }}>
                  <input type="text" className="form-control" style={{ fontFamily: 'var(--font-mono)' }} value={selectedDb.database_name} readOnly />
                  <button className="btn btn-secondary btn-sm" onClick={() => copyToClipboard(selectedDb.database_name, 'Database Name')}>
                    <Copy size={12} />
                  </button>
                </div>
              </div>

              <div>
                <label className="form-label">Database Username</label>
                <div style={{ display: 'flex', gap: '6px' }}>
                  <input type="text" className="form-control" style={{ fontFamily: 'var(--font-mono)' }} value={selectedDb.database_username} readOnly />
                  <button className="btn btn-secondary btn-sm" onClick={() => copyToClipboard(selectedDb.database_username, 'Username')}>
                    <Copy size={12} />
                  </button>
                </div>
              </div>

              <div>
                <label className="form-label">Database Password</label>
                <div style={{ display: 'flex', gap: '6px' }}>
                  <input
                    type={showPassword ? 'text' : 'password'}
                    className="form-control"
                    style={{ fontFamily: 'var(--font-mono)' }}
                    value={selectedDb.password}
                    readOnly
                  />
                  <button
                    className="btn btn-secondary btn-sm"
                    onClick={() => setShowPassword(!showPassword)}
                    title={showPassword ? 'Hide password' : 'Show password'}
                  >
                    {showPassword ? <EyeOff size={13} /> : <Eye size={13} />}
                  </button>
                  <button className="btn btn-secondary btn-sm" onClick={() => copyToClipboard(selectedDb.password, 'Password')}>
                    <Copy size={12} />
                  </button>
                </div>
              </div>

              <div>
                <label className="form-label">Connection String</label>
                <div className="mono-box">
                  <span style={{ wordBreak: 'break-all' }}>{selectedDb.connection_string}</span>
                  <button onClick={() => copyToClipboard(selectedDb.connection_string, 'Connection String')} style={{ color: 'var(--text-main)' }}>
                    <Copy size={13} />
                  </button>
                </div>
              </div>
            </div>

            <div className="modal-footer" style={{ justifyContent: 'space-between' }}>
              <button
                type="button"
                className="btn btn-danger btn-sm"
                onClick={() => handleResetPassword(selectedDb.id)}
                disabled={resetting}
              >
                <RefreshCw size={12} />
                {resetting ? 'Resetting...' : 'Reset Password'}
              </button>

              <button type="button" className="btn btn-secondary" onClick={() => setSelectedDb(null)}>
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default UserDatabases;

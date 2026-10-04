import React, { useEffect, useState } from 'react';
import api from '../../api/client';
import { Settings, Save, ShieldAlert, CheckCircle } from 'lucide-react';
import toast from 'react-hot-toast';

export const AdminSettings = () => {
  const [settings, setSettings] = useState({
    panel_name: '',
    support_email: '',
    allow_registration: 'true',
    maintenance_mode: 'false',
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    try {
      const res = await api.get('/admin/settings');
      setSettings(prev => ({ ...prev, ...res.data.settings }));
    } catch (err) {
      toast.error('Failed to load settings.');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      await api.put('/admin/settings', settings);
      toast.success('Panel settings saved successfully.');
    } catch (err) {
      toast.error('Failed to save settings.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <div style={{ color: 'var(--text-muted)', padding: '20px' }}>Loading configuration...</div>;
  }

  return (
    <div style={{ maxWidth: '750px' }}>
      <div style={{ marginBottom: '20px' }}>
        <h2 style={{ fontSize: '18px', fontWeight: 600 }}>Panel System Settings</h2>
        <p style={{ fontSize: '13px', color: 'var(--text-muted)' }}>
          Configure branding, support email, registration policies, and maintenance mode
        </p>
      </div>

      <div className="card">
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label className="form-label">Panel Brand Name</label>
            <input
              type="text"
              className="form-control"
              value={settings.panel_name}
              onChange={(e) => setSettings({ ...settings, panel_name: e.target.value })}
              required
            />
          </div>

          <div className="form-group">
            <label className="form-label">Support Email Address</label>
            <input
              type="email"
              className="form-control"
              value={settings.support_email}
              onChange={(e) => setSettings({ ...settings, support_email: e.target.value })}
            />
          </div>

          <div style={{ borderTop: '1px solid var(--border-color)', margin: '20px 0 16px', paddingTop: '16px' }}>
            <h4 style={{ fontSize: '14px', marginBottom: '14px', color: 'var(--text-main)' }}>Policy & System Access</h4>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>

              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <div>
                  <div style={{ fontSize: '13.5px', fontWeight: 600 }}>Allow Public Registration</div>
                  <div style={{ fontSize: '12px', color: 'var(--text-muted)' }}>
                    Permit new visitors to register accounts from the login page
                  </div>
                </div>
                <select
                  className="form-control"
                  style={{ width: '130px' }}
                  value={settings.allow_registration}
                  onChange={(e) => setSettings({ ...settings, allow_registration: e.target.value })}
                >
                  <option value="true">Allowed</option>
                  <option value="false">Disabled</option>
                </select>
              </div>

              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <div>
                  <div style={{ fontSize: '13.5px', fontWeight: 600 }}>Maintenance Mode</div>
                  <div style={{ fontSize: '12px', color: 'var(--text-muted)' }}>
                    Show maintenance banner and prevent new client orders
                  </div>
                </div>
                <select
                  className="form-control"
                  style={{ width: '130px' }}
                  value={settings.maintenance_mode}
                  onChange={(e) => setSettings({ ...settings, maintenance_mode: e.target.value })}
                >
                  <option value="false">Off (Normal)</option>
                  <option value="true">Maintenance</option>
                </select>
              </div>
            </div>
          </div>

          <div className="modal-footer" style={{ marginTop: '24px' }}>
            <button type="submit" className="btn btn-primary" disabled={saving}>
              <Save size={15} /> {saving ? 'Saving...' : 'Save Settings'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default AdminSettings;

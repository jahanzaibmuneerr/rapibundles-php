import React, { useState, useEffect } from 'react';
import axios from 'axios';
import './index.css';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:5001';

function App() {
  const [doctors, setDoctors] = useState([]);
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [selectedDate, setSelectedDate] = useState('');
  const [availableSlots, setAvailableSlots] = useState([]);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState(null);
  const [selectedSlot, setSelectedSlot] = useState(null);
  const [patientName, setPatientName] = useState('');

  useEffect(() => {
    fetchDoctors();
  }, []);

  useEffect(() => {
    if (selectedDoctor && selectedDate) {
      fetchAvailability();
    } else {
      setAvailableSlots([]);
    }
  }, [selectedDoctor, selectedDate]);

  const fetchDoctors = async () => {
    try {
      setLoading(true);
      const response = await axios.get(`${API_URL}/api/doctors`);
      setDoctors(response.data);
    } catch (error) {
      setMessage({
        type: 'error',
        text: `Failed to load doctors: ${error.message}`,
      });
    } finally {
      setLoading(false);
    }
  };

  const fetchAvailability = async () => {
    if (!selectedDoctor || !selectedDate) return;

    try {
      setLoading(true);
      setMessage(null);
      const response = await axios.get(
        `${API_URL}/api/doctors/${selectedDoctor.id}/availability`,
        {
          params: { date: selectedDate },
        }
      );
      setAvailableSlots(response.data.available_slots || []);
    } catch (error) {
      setMessage({
        type: 'error',
        text: error.response?.data?.message || `Failed to load availability: ${error.message}`,
      });
      setAvailableSlots([]);
    } finally {
      setLoading(false);
    }
  };

  const handleSlotSelect = (slot) => {
    setSelectedSlot(slot);
    setMessage(null);
  };

  const handleBookAppointment = async () => {
    if (!selectedDoctor || !selectedDate || !selectedSlot || !patientName.trim()) {
      setMessage({
        type: 'error',
        text: 'Please fill in all fields and select a time slot.',
      });
      return;
    }

    try {
      setLoading(true);
      setMessage(null);

      const startTime = `${selectedDate} ${selectedSlot}`;

      const response = await axios.post(
        `${API_URL}/api/appointments`,
        {
          doctor_id: selectedDoctor.id,
          patient_name: patientName,
          start_time: startTime,
        }
      );

      setMessage({
        type: 'success',
        text: `Appointment booked successfully! ${response.data.message}`,
      });

      setSelectedSlot(null);
      setPatientName('');
      
      setTimeout(() => {
        fetchAvailability();
      }, 500);

    } catch (error) {
      const status = error.response?.status;
      const errorMessage = error.response?.data?.message || error.message;

      if (status === 409) {
        setMessage({
          type: 'error',
          text: `❌ ${errorMessage} This demonstrates the concurrency control working!`,
        });
        setTimeout(() => {
          fetchAvailability();
        }, 500);
      } else {
        setMessage({
          type: 'error',
          text: `Failed to book appointment: ${errorMessage}`,
        });
      }
    } finally {
      setLoading(false);
    }
  };

  const getTodayDate = () => {
    const today = new Date();
    return today.toISOString().split('T')[0];
  };

  const getMinDate = () => {
    return getTodayDate();
  };

  return (
    <div className="container">
      <div className="header">
        <h1>🏥 Medical Appointment Booking</h1>
        <p className="subtitle">
          Select a doctor, choose a date, and book your appointment. 
          Try booking the same slot from multiple tabs to see our concurrency control in action!
        </p>
      </div>

      {message && (
        <div className={`message ${message.type}`}>
          <span>{message.type === 'success' ? '✅' : '❌'}</span>
          <span>{message.text}</span>
        </div>
      )}

      <h2>1. Select a Doctor</h2>
      {loading && !doctors.length ? (
        <div className="loading">Loading doctors</div>
      ) : (
        <div className="doctor-list">
          {doctors.map((doctor) => (
            <div
              key={doctor.id}
              className={`doctor-card ${
                selectedDoctor?.id === doctor.id ? 'selected' : ''
              }`}
              onClick={() => setSelectedDoctor(doctor)}
            >
              <span className="doctor-icon">👨‍⚕️</span>
              <div className="doctor-name">{doctor.name}</div>
              <div className="doctor-specialization">{doctor.specialization}</div>
            </div>
          ))}
        </div>
      )}

      {selectedDoctor && (
        <>
          <div className="date-section">
            <h2>2. Select a Date</h2>
            <div className="date-input">
              <input
                type="date"
                value={selectedDate}
                onChange={(e) => setSelectedDate(e.target.value)}
                min={getMinDate()}
              />
              <button
                className="btn btn-primary"
                onClick={fetchAvailability}
                disabled={!selectedDate || loading}
              >
                {loading ? '⏳ Checking...' : '📅 Check Availability'}
              </button>
            </div>
          </div>

          {availableSlots.length > 0 && (
            <>
              <div className="slots-section">
                <h2>3. Available Time Slots</h2>
                <div className="slots-grid">
                  {availableSlots.map((slot) => (
                    <button
                      key={slot}
                      className={`slot-button ${
                        selectedSlot === slot ? 'selected' : ''
                      }`}
                      onClick={() => handleSlotSelect(slot)}
                      disabled={loading}
                    >
                      🕐 {slot}
                    </button>
                  ))}
                </div>
              </div>

              {selectedSlot && (
                <div className="booking-form">
                  <h2>4. Book Appointment</h2>
                  <input
                    type="text"
                    placeholder="Enter patient name"
                    value={patientName}
                    onChange={(e) => setPatientName(e.target.value)}
                    disabled={loading}
                    onKeyPress={(e) => {
                      if (e.key === 'Enter' && !loading && patientName.trim()) {
                        handleBookAppointment();
                      }
                    }}
                  />
                  <button
                    className="btn btn-primary"
                    onClick={handleBookAppointment}
                    disabled={loading || !patientName.trim()}
                  >
                    {loading ? '⏳ Booking...' : '✅ Confirm Appointment'}
                  </button>
                </div>
              )}
            </>
          )}

          {selectedDate && availableSlots.length === 0 && !loading && (
            <div className="message error">
              <span>⚠️</span>
              <span>No available slots for this date. Please select another date.</span>
            </div>
          )}
        </>
      )}
    </div>
  );
}

export default App;



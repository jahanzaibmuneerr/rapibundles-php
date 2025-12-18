#!/bin/bash

# Concurrency Test Script
# This script attempts to book the same appointment slot multiple times simultaneously
# Only one request should succeed (201), others should return 409 Conflict

API_URL="http://localhost:8000"
DOCTOR_ID=1
PATIENT_NAME="Test Patient"
DATE=$(date -d "+1 day" +%Y-%m-%d 2>/dev/null || date -v+1d +%Y-%m-%d 2>/dev/null || echo "2024-01-15")
TIME="09:00"
START_TIME="${DATE} ${TIME}"

echo "Testing concurrency control..."
echo "Attempting to book: ${START_TIME}"
echo "Sending 5 simultaneous requests..."
echo ""

# Send 5 simultaneous requests
for i in {1..5}; do
  (
    response=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X POST "${API_URL}/api/appointments" \
      -H "Content-Type: application/json" \
      -d "{
        \"doctor_id\": ${DOCTOR_ID},
        \"patient_name\": \"${PATIENT_NAME} ${i}\",
        \"start_time\": \"${START_TIME}\"
      }")
    
    http_code=$(echo "$response" | grep "HTTP_STATUS" | cut -d: -f2)
    body=$(echo "$response" | sed '/HTTP_STATUS/d')
    
    if [ "$http_code" = "201" ]; then
      echo "✅ Request $i: SUCCESS (201 Created)"
    elif [ "$http_code" = "409" ]; then
      echo "❌ Request $i: CONFLICT (409) - Slot already booked"
    else
      echo "⚠️  Request $i: Unexpected status ($http_code)"
      echo "   Response: $body"
    fi
  ) &
done

wait
echo ""
echo "Test completed!"



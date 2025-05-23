<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    // If AJAX, return JSON error
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    } else {
        header('Location: ../../auth/index.php');
        exit();
    }
}

if ($_SESSION['role'] !== 'student') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied!']);
        exit;
    } else {
        echo "Access denied!";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>
    <link rel="stylesheet" href="../../css/calendar.css">
</head>
<body>
    <div class="main-flex-wrapper">
        <?php include 'sidebar_student.php'; ?>
        <div class="calendar-main-wrapper">
            <div class="calendar-card">
                <div class="calendar-container">
                    <?php
                    // Include database connection
                    require_once '../../auth/db_connection.php';

                    // Get current month and year
                    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
                    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

                    // Create calendar header
                    $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
                    echo "<div class='calendar-header'>";
                    echo "<h2>$monthName $year</h2>";
                    
                    // Navigation buttons
                    $prevMonth = $month - 1;
                    $prevYear = $year;
                    if ($prevMonth < 1) {
                        $prevMonth = 12;
                        $prevYear--;
                    }
                    
                    $nextMonth = $month + 1;
                    $nextYear = $year;
                    if ($nextMonth > 12) {
                        $nextMonth = 1;
                        $nextYear++;
                    }
                    
                    echo "<div class='calendar-nav'>";
                    echo "<a href='?month=$prevMonth&year=$prevYear' class='nav-btn'>&lt;</a>";
                    echo "<a href='?month=$nextMonth&year=$nextYear' class='nav-btn'>&gt;</a>";
                    echo "</div>";
                    echo "</div>";

                    // Create calendar grid with inline style for debugging
                    echo "<div class='calendar-grid' style='display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px;'>";
                    
                    // Days of week header
                    $daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    foreach ($daysOfWeek as $day) {
                        echo "<div class='calendar-day-header' style='border: 1px solid #ccc;'>$day</div>";
                    }

                    // Get first day of month
                    $firstDay = mktime(0, 0, 0, $month, 1, $year);
                    $startingDay = date('w', $firstDay);
                    $daysInMonth = date('t', $firstDay);
                    $currentDay = 1;
                    $cells = 0;

                    // Load events from database
                    $events = [];
                    $monthStart = sprintf('%04d-%02d-01', $year, $month);
                    $monthEnd = date('Y-m-t', strtotime($monthStart));

                    $stmt = $conn->prepare("SELECT event_date, event_text FROM calendar_events WHERE event_date BETWEEN ? AND ?");
                    $stmt->bind_param("ss", $monthStart, $monthEnd);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        // Store the event with the date in YYYY-MM-DD format
                        $events[$row['event_date']] = $row['event_text'];
                    }
                    $stmt->close();

                    // Helper to get event for a date
                    function getEvent($year, $month, $day, $events) {
                        $key = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        return isset($events[$key]) ? $events[$key] : null;
                    }

                    // 6 weeks x 7 days = 42 cells
                    for ($i = 0; $i < 6; $i++) {
                        for ($j = 0; $j < 7; $j++) {
                            if ($i === 0 && $j < $startingDay) {
                                echo "<div class='calendar-day empty' style='border: 1px solid #ccc;'></div>";
                            } elseif ($currentDay > $daysInMonth) {
                                echo "<div class='calendar-day empty' style='border: 1px solid #ccc;'></div>";
                            } else {
                                $isToday = ($currentDay == date('j') && $month == date('n') && $year == date('Y')) ? 'today' : '';
                                $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
                                $event = isset($events[$dateKey]) ? $events[$dateKey] : null;
                                $hasEvent = $event ? 'has-event' : '';
                                $eventData = $event ? htmlspecialchars($event) : '';
                                echo "<div class='calendar-day $isToday $hasEvent' data-day='$currentDay' data-event='$eventData' style='border: 1px solid #ccc; cursor:pointer;'>$currentDay";
                                if ($event) {
                                    echo "<span class='event-dot'></span>";
                                }
                                echo "</div>";
                                $currentDay++;
                            }
                            $cells++;
                        }
                    }
                    echo "</div>";
                    ?>
                </div>
                <div id="event-details-panel" class="event-details-panel">
                    <h3 id="event-details-date">Select a date</h3>
                    <div id="event-details-content">No Events</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Modal -->
    <div id="event-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" id="close-modal">&times;</span>
            <h3 id="modal-date"></h3>
            <form id="event-form">
                <input type="hidden" id="event-date" name="date">
                <textarea id="event-text" name="event" rows="4" placeholder="Add your event..."></textarea>
                <button type="submit">Save Event</button>
            </form>
            <div id="event-message"></div>
            <div class="saved-events-container">
                <h4>Saved Events</h4>
                <div id="saved-events-list"></div>
            </div>
        </div>
    </div>

    <script>
    // Modal logic
    const modal = document.getElementById('event-modal');
    const closeModal = document.getElementById('close-modal');
    const eventForm = document.getElementById('event-form');
    const eventText = document.getElementById('event-text');
    const eventDateInput = document.getElementById('event-date');
    const modalDate = document.getElementById('modal-date');
    const eventMessage = document.getElementById('event-message');
    const savedEventsList = document.getElementById('saved-events-list');

    // Side panel elements
    const eventDetailsPanel = document.getElementById('event-details-panel');
    const eventDetailsDate = document.getElementById('event-details-date');
    const eventDetailsContent = document.getElementById('event-details-content');

    // Function to load saved events
    function loadSavedEvents(date) {
        fetch(`get_events.php?date=${date}`)
            .then(res => res.json())
            .then(events => {
                savedEventsList.innerHTML = '';
                if (events.length === 0) {
                    savedEventsList.innerHTML = '<p class="no-events">No saved events</p>';
                    return;
                }
                events.forEach(event => {
                    const eventDiv = document.createElement('div');
                    eventDiv.className = 'saved-event-item';
                    eventDiv.innerHTML = `
                        <div class="event-text">${event.event_text}</div>
                        <button class="delete-event-btn" data-date="${event.event_date}">Delete</button>
                    `;
                    savedEventsList.appendChild(eventDiv);
                });

                // Add delete event listeners
                document.querySelectorAll('.delete-event-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        if (confirm('Are you sure you want to delete this event?')) {
                            const date = this.getAttribute('data-date');
                            const formData = new FormData();
                            formData.append('date', date);
                            formData.append('event', '');  // Empty event for deletion

                            // Fetch request to delete the event
                            fetch('calendar.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    window.location.reload();
                                }
                            });
                        }
                    });
                });
            })
            .catch(error => {
                console.error('Error loading events:', error);
                savedEventsList.innerHTML = '<p class="error-message">Error loading events</p>';
            });
    }


    // Open modal on day click & update side panel
    document.querySelectorAll('.calendar-day:not(.empty)').forEach(day => {
        day.addEventListener('click', function(e) {
            const dayNum = this.getAttribute('data-day');
            const month = <?php echo $month; ?>;
            const year = <?php echo $year; ?>;
            const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
            const eventTextVal = this.getAttribute('data-event') || '';
            // Update side panel
            eventDetailsDate.textContent = `Events for ${dateStr}`;
            eventDetailsContent.textContent = eventTextVal ? eventTextVal : 'No Events';
            // Open modal only if double-clicked
            if (e.detail === 2) {
                modalDate.textContent = `Event for ${dateStr}`;
                eventDateInput.value = dateStr;
                eventText.value = eventTextVal;
                eventMessage.textContent = '';
                modal.style.display = 'block';
                // Load saved events when modal opens
                loadSavedEvents(dateStr);
            }
        });
    });

    // Close modal
    closeModal.onclick = function() {
        modal.style.display = 'none';
    };
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };

    // Save event via AJAX
    eventForm.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(eventForm);
        const dateStr = eventDateInput.value;
        fetch('calendar.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            console.log('AJAX response:', data); // Debugging line
            eventMessage.textContent = data.message;
            if (data.success) {
                eventMessage.style.color = '#28a745'; // green
                // Update side panel immediately
                eventDetailsDate.textContent = `Events for ${dateStr}`;
                eventDetailsContent.textContent = eventText.value ? eventText.value : 'No Events';
                // Refresh saved events list in modal
                loadSavedEvents(dateStr);
                // Update the calendar day cell instantly
                const allDays = document.querySelectorAll('.calendar-day:not(.empty)');
                allDays.forEach(day => {
                    if (day.getAttribute('data-day') === String(Number(dateStr.split('-')[2]))) {
                        day.setAttribute('data-event', eventText.value);
                        if (eventText.value) {
                            day.classList.add('has-event');
                            if (!day.querySelector('.event-dot')) {
                                const dot = document.createElement('span');
                                dot.className = 'event-dot';
                                day.appendChild(dot);
                            }
                        } else {
                            day.classList.remove('has-event');
                            const dot = day.querySelector('.event-dot');
                            if (dot) dot.remove();
                        }
                    }
                });
                alert('Event saved successfully!');
            } else {
                eventMessage.style.color = '#dc3545'; // red
            }
        });
    };
    </script>

    <style>
    .saved-events-container {
        margin-top: 24px;
        border-top: 1px solid #e3eaf1;
        padding-top: 16px;
    }

    .saved-events-container h4 {
        color: #006DB0;
        font-size: 1.1rem;
        margin-bottom: 12px;
        font-family: 'Rowdies', Arial, sans-serif;
    }

    .saved-event-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        background: #f6f8fa;
        border-radius: 8px;
        margin-bottom: 8px;
        border: 1px solid #e3eaf1;
    }

    .event-text {
        flex: 1;
        margin-right: 12px;
        font-family: 'Rowdies', Arial, sans-serif;
        color: #333;
    }

    .delete-event-btn {
        background: #dc3545;
        color: #fff;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.85rem;
        transition: background 0.2s;
        font-family: 'Rowdies', Arial, sans-serif;
        box-shadow: 0 1px 4px rgba(220, 53, 69, 0.2);
    }

    .delete-event-btn:hover {
        background: #c82333;
    }

    .no-events {
        color: #666;
        font-style: italic;
        text-align: center;
        padding: 12px;
        font-family: 'Rowdies', Arial, sans-serif;
    }

    .error-message {
        color: #dc3545;
        text-align: center;
        padding: 12px;
        font-family: 'Rowdies', Arial, sans-serif;
    }
    </style>

    <?php
    // Handle AJAX event save
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date'])) {
        $date = $_POST['date'];
        $event = trim($_POST['event']);
    
        if ($event) {
            // Insert or update event
            $stmt = $conn->prepare("INSERT INTO calendar_events (event_date, event_text) 
                                VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE event_text = ?");
            $stmt->bind_param("sss", $date, $event, $event);
        } else {
            // Delete event if text is empty
            $stmt = $conn->prepare("DELETE FROM calendar_events WHERE event_date = ?");
            $stmt->bind_param("s", $date);
        }
    
        // Execute query and check success
        $success = $stmt->execute();
        $stmt->close();
        
        // Ensure to return a valid JSON response
        header('Content-Type: application/json');
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => $event ? 'Event saved!' : 'Event deleted successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $event ? 'Error saving event' : 'Error deleting event'
            ]);
        }
        exit;
    }

    ?>
</body>
</html>

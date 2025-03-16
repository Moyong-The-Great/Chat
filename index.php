<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'dbcon.php'; // Ensure database connection is included

$_SESSION['user_id'] = 1; // Simulating a logged-in user for testing

$loggedInUserId = $_SESSION['user_id'] ?? null;

// Check if logged-in user is set
if (!$loggedInUserId) {
    die("User not logged in");
}

// SQL Query to fetch chat list with last message and unread count
$query = "
    SELECT u.user_id, u.name, u.email, 
           (SELECT m.content 
            FROM messages m 
            WHERE (m.sender_id = u.user_id AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = u.user_id) 
            ORDER BY m.sent_at DESC 
            LIMIT 1) AS last_message,
           
           (SELECT m.sent_at 
            FROM messages m 
            WHERE (m.sender_id = u.user_id AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = u.user_id) 
            ORDER BY m.sent_at DESC 
            LIMIT 1) AS last_chat_time,

           (SELECT COUNT(*) 
            FROM messages m 
            WHERE m.receiver_id = ? 
              AND m.sender_id = u.user_id 
              AND m.status = 'unread') AS unread_count 

    FROM users u
    WHERE u.user_id != ?
    ORDER BY CASE WHEN last_chat_time IS NULL THEN 1 ELSE 0 END, last_chat_time DESC
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iiiiii", $loggedInUserId, $loggedInUserId, $loggedInUserId, $loggedInUserId, $loggedInUserId, $loggedInUserId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

mysqli_close($conn);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Chat</title>
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <link rel="stylesheet" href="https://cdn.oesmith.co.uk/morris-0.5.1.css">
    <link rel="stylesheet" href="assets/plugins/morris/morris.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f0f0f0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .chat-container {
        width: 400px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .chat-header {
        background: #007bff;
        color: white;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .chat-body {
        height: 400px;
        overflow-y: auto;
        padding: 10px;
        background: #f9f9f9;
    }

    .chat-footer {
        display: flex;
        padding: 10px;
        background: white;
        border-top: 1px solid #ddd;
    }

    .input-group {
        display: flex;
        width: 100%;
    }

    .input-msg-send {
        flex-grow: 1;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .btn-file {
        cursor: pointer;
    }

    .msg-send-btn {
        background: #007bff;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
    }

    .chat-list {
        list-style: none;
        padding: 0;
    }

    .chat-list .sent,
    .chat-list .received {
        display: flex;
        margin-bottom: 10px;
    }

    .chat-list .sent {
        justify-content: flex-end;
    }

    .chat-list .received {
        justify-content: flex-start;
    }

    .msg-box {
        max-width: 60%;
        padding: 8px;
        background: #007bff;
        color: white;
        border-radius: 10px;
    }

    .chat-time {
        font-size: 12px;
        text-align: right;
    }
</style>

<body>
    <div class="main-wrapper">
        <div class="page-wrapper">
            <div class="content container-fluid">
                <div class="row mt-5">
                    <div class="col-sm-12">
                        <div class="chat-window d-flex">

                            <!-- Left Chat Sidebar -->
                            <div class="chat-cont-left flex-shrink-0">
                                <div class="chat-header">
                                    <span>Chats</span>
                                </div>

                                <form class="chat-search">
                                    <div class="input-group">
                                        <div class="input-group-prepend"> <i class="fas fa-search"></i> </div>
                                        <input type="text" class="form-control" placeholder="Search" id="userSearch">
                                    </div>
                                </form>

                                <div class="chat-users-list overflow-auto" id="chatUsersList">
                                    <?php foreach ($users as $user): ?>
                                        <?php if ($user['user_id'] == $loggedInUserId) continue; ?>
                                        <?php
                                        $avatar = !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'assets/img/profiles/default-avatar.jpg';
                                        $status = htmlspecialchars($user['status'] ?? 'offline');
                                        ?>
                                        <a href="javascript:void(0);" class="media chat-user"
                                            data-id="<?php echo htmlspecialchars($user['user_id']); ?>"
                                            data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                            data-avatar="<?php echo $avatar; ?>"
                                            data-status="<?php echo $status; ?>">
                                            <div class="media-img-wrap">
                                                <div class="avatar avatar-<?php echo $status; ?>">
                                                    <img src="<?php echo $avatar; ?>" alt="User Image" class="avatar-img rounded-circle">
                                                </div>
                                            </div>
                                            <div class="media-body">
                                                <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                                <div class="user-last-chat"><?php echo htmlspecialchars($user['last_message'] ?? 'No messages yet'); ?></div>
                                            </div>
                                            <div class="chat-time-notification">
                                                <div class="last-chat-time">
                                                    <?php echo !empty($user['last_chat_time']) ? date('g:i A', strtotime($user['last_chat_time'])) : ''; ?>
                                                </div>
                                                <?php if (!empty($user['unread_count']) && $user['unread_count'] > 0): ?>
                                                    <div class="badge badge-success badge-pill"><?php echo $user['unread_count']; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Right Chat Panel -->
                            <div class="chat-cont-right flex-grow-1 d-flex flex-column">
                                <!-- Chat Header -->
                                <div class="chat-header d-flex align-items-center p-3 border-bottom">
                                    <a id="back_user_list" href="javascript:void(0)" class="back-user-list me-2">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <div class="media d-flex align-items-center">
                                        <div class="media-img-wrap me-2">
                                            <div class="avatar avatar-offline">
                                                <img src="default-avatar.jpg" alt="User Image" class="avatar-img rounded-circle">
                                            </div>
                                        </div>
                                        <div class="media-body">
                                            <div class="user-name">Select a user</div>
                                            <div class="user-status">offline</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Chat Body -->
                                <div class="chat-body flex-grow-1 overflow-auto p-3">
                                    <ul class="chat-list list-unstyled">
                                        <!-- Messages will be dynamically appended here -->
                                    </ul>
                                </div>

                                <!-- Chat Footer -->
                                <div class="chat-footer p-3 border-top">
                                    <div class="input-group">
                                        <input type="text" id="chat-input" class="form-control" placeholder="Type something">
                                        <button type="button" id="send-btn" class="btn btn-primary">

                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            </div> <!-- End chat right panel -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
    const chatUsers = document.querySelectorAll(".chat-user");
    const chatListContainer = document.getElementById("chatUsersList");
    const chatHeader = document.querySelector(".chat-header .media");
    const chatMessages = document.querySelector(".chat-list");
    const chatRightPanel = document.querySelector(".chat-cont-right");
    const sendMessageButton = document.getElementById("send-btn");
    const messageInput = document.getElementById("chat-input");
    let selectedUserId = null;
    let chatRefreshInterval = null; // To store the interval for real-time updates

    // Get logged-in user ID from PHP session
    const CURRENT_USER_ID = "<?php echo $_SESSION['user_id'] ?? ''; ?>";

    // Fetch the latest chat user from PHP variable
    const latestUserId = "<?php echo $latestChatUserId ?? ''; ?>";
    if (latestUserId) {
        loadChat(latestUserId);
    }

    // Function to load chat messages (with auto-refresh)
    function loadChat(userId) {
        if (!userId || userId === selectedUserId) return; // Prevent reloading same chat
        selectedUserId = userId;

        // Find selected user in the chat list
        const selectedUser = [...chatUsers].find(u => u.dataset.id === userId);
        if (!selectedUser) return;

        // Update chat header with user details
        chatHeader.querySelector(".user-name").textContent = selectedUser.dataset.name;
        chatHeader.querySelector(".user-status").textContent = selectedUser.dataset.status;
        chatHeader.querySelector(".avatar-img").src = selectedUser.dataset.avatar;

        // Show loading message
        chatMessages.innerHTML = `<li class="loading">Loading chat with ${selectedUser.dataset.name}...</li>`;
        chatRightPanel.style.display = "block";

        // Clear previous interval if exists
        if (chatRefreshInterval) clearInterval(chatRefreshInterval);

        // Function to fetch messages
        function fetchMessages() {
            fetch(`fetch_message.php?user_id=${userId}`)
                .then(response => response.json())
                .then(messages => {
                    chatMessages.innerHTML = ""; // Clear previous messages

                    if (messages.length === 0) {
                        chatMessages.innerHTML = `<li class="no-messages">No messages yet</li>`;
                        return;
                    }

                    messages.forEach(msg => {
                        const msgClass = msg.sender_id == CURRENT_USER_ID ? "sent" : "received";
                        const messageText = msg.content ? msg.content : "(No message content)";

                        chatMessages.innerHTML += `
                            <li class="${msgClass}">
                                <div class="message-content">
                                    ${messageText}
                                    <span class="message-time">${msg.sent_at}</span>
                                </div>
                            </li>
                        `;
                    });

                    chatMessages.scrollTop = chatMessages.scrollHeight; // Auto-scroll to latest message

                    // Update latest message in chat user list
                    updateLastMessage(userId, messages[messages.length - 1].content);
                })
                .catch(error => {
                    console.error("Error loading chat:", error);
                    chatMessages.innerHTML = `<li class="error-message">Failed to load messages.</li>`;
                });
        }

        fetchMessages(); // Load messages initially
        chatRefreshInterval = setInterval(fetchMessages, 1000); // Refresh every 3 seconds
    }

    // Function to send a message
    function sendMessage() {
    const messageText = messageInput.value.trim();
    if (!messageText || !selectedUserId) {
        alert("Please select a user and enter a message!");
        return;
    }

    console.log("Attempting to send message:", messageText, "to User ID:", selectedUserId);

    fetch("send_message.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ user_id: selectedUserId, message: messageText })
    })
    .then(response => response.json())
    .then(data => {
        console.log("Server Response:", data);

        if (data.success) {
            // ✅ Clear input field
            messageInput.value = "";
            messageInput.innerHTML = "";
            messageInput.dispatchEvent(new Event("input")); // Ensures UI update
            messageInput.focus(); // Keeps focus for next input

            console.log("Message sent! Input cleared.");

            // ✅ Refresh chat messages
            loadChat(selectedUserId);
        } else {
            alert("Failed to send message: " + (data.error || "Unknown error"));
        }
    })
    .catch(error => {
        console.error("Error sending message:", error);
    });
}



    // Function to update the last message in the chat user list
    function updateLastMessage(userId, lastMessage) {
        const userElement = document.querySelector(`.chat-user[data-id="${userId}"]`);
        if (userElement) {
            userElement.querySelector(".user-last-chat").textContent = lastMessage;
        }
    }

    // Add event listeners to chat users
    chatUsers.forEach(user => {
        user.addEventListener("click", function () {
            document.querySelectorAll(".chat-user").forEach(u => u.classList.remove("active"));
            this.classList.add("active");
            loadChat(this.dataset.id);
            chatListContainer.style.display = "block";
        });
    });

    // Add event listeners for sending messages
    sendMessageButton.addEventListener("click", sendMessage);
    messageInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            sendMessage();
        }
    });
});

</script>

    <script src="assets/js/jquery-3.5.1.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>

    <style>

    </style>






    <style>
        .chat-users-list {
            max-height: 600px;
            overflow-y: auto;
            padding: 10px;
            background-color: #fff;
            border-radius: 8px;
        }

        .chat-user {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 5px;
            background-color: #fff;
            border-radius: 8px;
            transition: background 0.3s ease-in-out;
            text-decoration: none;
            color: #333;
            position: relative;
        }

        .chat-user:hover {
            background-color: #f5f5f5;
        }

        .media-img-wrap {
            position: relative;
            width: 50px;
            height: 50px;
            margin-right: 12px;
        }

        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        /* Status Dot */
        .avatar::after {
            content: "";
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid #fff;
            background-color: #ff9900;
            /* Orange dot */
        }

        .media-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: #000;
        }

        .user-last-chat {
            font-size: 13px;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }

        .chat-time-notification {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            font-size: 12px;
            color: #6c757d;
        }

        /* Unread Messages Badge */
        .badge-success {
            background-color: #5865f2;
            color: #fff;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 20px;
            font-weight: bold;
        }
    </style>
</body>


</html>
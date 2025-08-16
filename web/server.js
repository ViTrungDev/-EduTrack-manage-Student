const express = require("express");
const http = require("http");
const { Server } = require("socket.io");
const mysql = require("mysql2/promise");

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
  cors: {
    origin: "*", // cho phép frontend kết nối (có thể fix domain cụ thể sau)
  },
});

// Kết nối DB MySQL
const db = mysql.createPool({
  host: "localhost",
  user: "root",
  password: "123456789",
  database: "edutrack",
});

// Sự kiện client kết nối
io.on("connection", (socket) => {
  console.log("User connected:", socket.id);

  // Join room theo TicketID
  socket.on("joinTicket", async ({ ticketId, userId }) => {
    socket.join(`ticket_${ticketId}`);
    console.log(`User ${userId} joined ticket ${ticketId}`);

    // Load tin nhắn cũ
    const [rows] = await db.query(
      "SELECT c.*, u.FullName FROM TicketChats c JOIN Users u ON c.SenderID = u.UserID WHERE TicketID = ? ORDER BY CreatedAt ASC",
      [ticketId]
    );
    socket.emit("chatHistory", rows);
  });

  // Nhận tin nhắn
  socket.on("sendMessage", async ({ ticketId, userId, message }) => {
    if (!message.trim()) return;

    // Lưu DB
    await db.query(
      "INSERT INTO TicketChats (TicketID, SenderID, Message) VALUES (?, ?, ?)",
      [ticketId, userId, message]
    );

    // Broadcast cho room đó
    io.to(`ticket_${ticketId}`).emit("newMessage", {
      ticketId,
      userId,
      message,
      createdAt: new Date(),
    });
  });

  socket.on("disconnect", () => {
    console.log("User disconnected:", socket.id);
  });
});

server.listen(3001, () => {
  console.log("WebSocket server running on port 3001");
});

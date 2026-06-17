'use client';

import { useState, useEffect } from 'react';
import { ChatInterface } from '../../components/ChatInterface';
import { Sidebar } from '../../components/Sidebar';

export default function ChatPage() {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [conversations, setConversations] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    setIsAuthenticated(!!token);

    if (token) {
      fetch('/api/chat/conversations', {
        headers: { Authorization: `Bearer ${token}` },
      })
        .then((r) => r.json())
        .then((data) => setConversations(data.data || []))
        .catch(() => {})
        .finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, []);

  if (loading) {
    return (
      <div className="flex h-screen items-center justify-center">
        <div className="text-slate-400">Ładowanie...</div>
      </div>
    );
  }

  return (
    <div className="flex h-screen overflow-hidden bg-slate-50">
      {isAuthenticated && (
        <Sidebar conversations={conversations} onNewChat={() => {}} />
      )}
      <main className="flex-1 md:ml-72">
        <ChatInterface isAuthenticated={isAuthenticated} />
      </main>
    </div>
  );
}

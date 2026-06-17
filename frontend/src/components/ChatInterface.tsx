'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { PaywallModal } from './PaywallModal';

interface Message {
  role: 'user' | 'assistant' | 'system';
  content: string;
}

const GUEST_STORAGE_KEY = 'guest_chat';
const GUEST_LIMIT = Number(process.env.NEXT_PUBLIC_GUEST_MESSAGE_LIMIT) || 2;

export function ChatInterface({ isAuthenticated }: { isAuthenticated: boolean }) {
  const router = useRouter();
  const [messages, setMessages] = useState<Message[]>(loadGuestChat());
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPaywall, setShowPaywall] = useState(false);
  const [error, setError] = useState('');

  function loadGuestChat(): Message[] {
    if (isAuthenticated || typeof window === 'undefined') return [];
    try {
      return JSON.parse(localStorage.getItem(GUEST_STORAGE_KEY) || '[]');
    } catch {
      return [];
    }
  }

  function saveGuestChat(msgs: Message[]) {
    if (isAuthenticated) return;
    localStorage.setItem(GUEST_STORAGE_KEY, JSON.stringify(msgs));
  }

  async function send() {
    const content = input.trim();
    if (!content || loading) return;

    setError('');
    const userMsg: Message = { role: 'user', content };
    const newMessages = [...messages, userMsg];
    setMessages(newMessages);
    setInput('');
    setLoading(true);

    try {
      if (isAuthenticated) {
        await sendAuthenticated(content);
      } else {
        await sendGuest(content, newMessages);
      }
    } catch (err: any) {
      setError(err.message || 'Wystąpił błąd. Spróbuj ponownie.');
    } finally {
      setLoading(false);
    }
  }

  async function sendGuest(content: string, allMessages: Message[]) {
    const res = await fetch('/api/chat/guest', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ content, history: messages }),
    });

    const data = await res.json();

    if (res.status === 429 || data.blocked) {
      // PAYWALL — po 2 wiadomościach
      saveGuestChat(allMessages); // zachowaj rozmowę dla przeniesienia po rejestracji
      setShowPaywall(true);
      return;
    }

    const assistantMsg: Message = { role: 'assistant', content: data.message.content };
    const updated = [...allMessages, assistantMsg];
    setMessages(updated);
    saveGuestChat(updated);
  }

  async function sendAuthenticated(content: string) {
    const token = localStorage.getItem('auth_token');
    const res = await fetch('/api/chat/messages', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ content }),
    });

    const data = await res.json();

    if (res.status === 402) {
      // Brak salda → panel doładowania
      router.push('/dashboard?tab=billing');
      return;
    }

    if (!res.ok) throw new Error(data.message);

    setMessages((prev) => [
      ...prev,
      { role: 'assistant', content: data.message.content },
    ]);
  }

  return (
    <div className="flex h-full flex-col">
      {/* Lista wiadomości */}
      <div className="chat-scroll flex-1 overflow-y-auto px-4 py-6">
        <div className="mx-auto max-w-3xl space-y-4">
          {messages.length === 0 && (
            <div className="mt-20 text-center text-slate-400">
              <p className="text-lg">Witaj! Jestem wirtualnym ekspertem IT.</p>
              <p className="text-sm">Zapytaj o dowolny problem informatyczny.</p>
            </div>
          )}
          {messages.map((msg, i) => (
            <MessageBubble key={i} message={msg} />
          ))}
          {loading && <TypingIndicator />}
          {error && <div className="text-center text-sm text-red-500">{error}</div>}
        </div>
      </div>

      {/* Pole wpisywania */}
      <div className="border-t border-slate-200 bg-white px-4 py-4">
        <div className="mx-auto flex max-w-3xl items-end gap-2">
          <textarea
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                send();
              }
            }}
            placeholder="Napisz swoją wiadomość..."
            rows={1}
            className="flex-1 resize-none rounded-xl border border-slate-300 px-4 py-3 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
          />
          <button
            onClick={send}
            disabled={loading || !input.trim()}
            className="rounded-xl bg-brand-600 px-6 py-3 font-semibold text-white hover:bg-brand-700 disabled:opacity-40"
          >
            Wyślij
          </button>
        </div>
        {!isAuthenticated && (
          <p className="mx-auto mt-2 max-w-3xl text-center text-xs text-slate-400">
            Pozostało darmowych wiadomości: {Math.max(0, GUEST_LIMIT - countUserMessages(messages))}
          </p>
        )}
      </div>

      {showPaywall && <PaywallModal onClose={() => setShowPaywall(false)} />}
    </div>
  );
}

function countUserMessages(msgs: Message[]): number {
  return msgs.filter((m) => m.role === 'user').length;
}

function MessageBubble({ message }: { message: Message }) {
  const isUser = message.role === 'user';
  return (
    <div className={`flex ${isUser ? 'justify-end' : 'justify-start'}`}>
      <div
        className={`max-w-[80%] whitespace-pre-wrap rounded-2xl px-4 py-3 ${
          isUser
            ? 'bg-brand-600 text-white'
            : 'border border-slate-200 bg-white text-slate-800'
        }`}
      >
        {message.content}
      </div>
    </div>
  );
}

function TypingIndicator() {
  return (
    <div className="flex justify-start">
      <div className="flex gap-1 rounded-2xl border border-slate-200 bg-white px-4 py-3">
        <span className="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:0ms]" />
        <span className="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:150ms]" />
        <span className="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:300ms]" />
      </div>
    </div>
  );
}

'use client';

import Link from 'next/link';
import { useState, useEffect } from 'react';

interface Conversation {
  id: number;
  title: string;
  created_at: string;
}

export function Sidebar({
  conversations,
  activeId,
  onNewChat,
}: {
  conversations: Conversation[];
  activeId?: number;
  onNewChat: () => void;
}) {
  const [open, setOpen] = useState(false);

  return (
    <>
      {/* Przycisk mobilny */}
      <button
        onClick={() => setOpen(!open)}
        className="fixed left-4 top-4 z-40 rounded-lg bg-white p-2 shadow md:hidden"
      >
        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      {/* Overlay mobilny */}
      {open && <div className="fixed inset-0 z-30 bg-black/30 md:hidden" onClick={() => setOpen(false)} />}

      <aside
        className={`fixed inset-y-0 left-0 z-30 w-72 transform border-r border-slate-200 bg-slate-900 text-white transition-transform md:translate-x-0 ${
          open ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="flex h-full flex-col p-3">
          <div className="mb-4 px-2 py-2 text-xl font-bold text-brand-400">
            IT<span className="text-white">Expert</span>
          </div>

          <button
            onClick={onNewChat}
            className="mb-4 flex items-center gap-2 rounded-lg border border-slate-700 px-3 py-2.5 text-sm font-medium hover:bg-slate-800"
          >
            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            Nowa rozmowa
          </button>

          {/* Historia rozmów */}
          <div className="flex-1 space-y-1 overflow-y-auto">
            {conversations.map((conv) => (
              <Link
                key={conv.id}
                href={`/chat?c=${conv.id}`}
                className={`block truncate rounded-lg px-3 py-2 text-sm hover:bg-slate-800 ${
                  activeId === conv.id ? 'bg-slate-800 text-brand-300' : 'text-slate-300'
                }`}
              >
                {conv.title}
              </Link>
            ))}
            {conversations.length === 0 && (
              <p className="px-3 py-2 text-xs text-slate-500">Brak rozmów</p>
            )}
          </div>

          {/* Linki dolne */}
          <div className="mt-4 space-y-1 border-t border-slate-700 pt-3">
            <Link href="/dashboard" className="block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800">
              Panel użytkownika
            </Link>
            <Link href="/user/settings" className="block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800">
              Ustawienia
            </Link>
          </div>
        </div>
      </aside>
    </>
  );
}

import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'IT Expert Chat — Pomoc informatyczna 24/7',
  description: 'Wirtualny ekspert informatyczny. Uzyskaj profesjonalną pomoc IT w kilka sekund.',
  keywords: ['pomoc informatyczna', 'ekspert IT', 'wsparcie IT', 'chatbot IT'],
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="pl">
      <body className="antialiased text-slate-900">{children}</body>
    </html>
  );
}

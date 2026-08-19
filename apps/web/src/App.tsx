import './App.css'
import { useMemo, useState } from 'react'
import type { FormEvent } from 'react'

function App() {
  const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8080'
  const query = useMemo(() => new URLSearchParams(window.location.search), [])
  const [message, setMessage] = useState(
    query.get('auth') === 'google'
      ? 'Acesso com Google confirmado.'
      : query.has('auth_error')
        ? 'Não foi possível entrar com esta conta Google.'
        : '',
  )
  const [loading, setLoading] = useState(false)

  async function login(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setLoading(true)
    setMessage('')

    const form = new FormData(event.currentTarget)

    try {
      await fetch(`${apiUrl}/sanctum/csrf-cookie`, { credentials: 'include' })
      const xsrfToken = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.split('=')[1]

      const response = await fetch(`${apiUrl}/login`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          ...(xsrfToken ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) } : {}),
        },
        body: JSON.stringify({
          email: form.get('email'),
          password: form.get('password'),
        }),
      })

      setMessage(response.ok ? 'Acesso confirmado.' : 'E-mail ou senha inválidos.')
    } catch {
      setMessage('A API não está disponível. Tente novamente.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <main>
      <p className="eyebrow">Gestão pastoral</p>
      <h1>eclEZapp</h1>
      <p className="lead">
        Entre com a conta convidada pela sua paróquia.
      </p>

      <section className="login-card" aria-labelledby="login-title">
        <h2 id="login-title">Acessar</h2>
        <form onSubmit={login}>
          <label htmlFor="email">E-mail</label>
          <input id="email" name="email" type="email" autoComplete="email" required />

          <label htmlFor="password">Senha</label>
          <input id="password" name="password" type="password" autoComplete="current-password" required />

          <button type="submit" disabled={loading}>
            {loading ? 'Entrando…' : 'Entrar'}
          </button>
        </form>

        <div className="separator"><span>ou</span></div>

        <a className="google-button" href={`${apiUrl}/auth/google/redirect`}>
          Entrar com Google
        </a>

        <p className="invitation-note">O acesso é permitido somente para contas previamente convidadas.</p>
        {message && <p className="feedback" role="status">{message}</p>}
      </section>
    </main>
  )
}

export default App

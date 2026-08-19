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
  const [mode, setMode] = useState<'login' | 'register'>('login')

  async function sessionHeaders() {
    await fetch(`${apiUrl}/sanctum/csrf-cookie`, { credentials: 'include' })
    const xsrfToken = document.cookie
      .split('; ')
      .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
      ?.split('=')[1]

    return {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(xsrfToken ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) } : {}),
    }
  }

  async function login(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setLoading(true)
    setMessage('')

    const form = new FormData(event.currentTarget)

    try {
      const response = await fetch(`${apiUrl}/login`, {
        method: 'POST',
        credentials: 'include',
        headers: await sessionHeaders(),
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

  async function register(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setLoading(true)
    setMessage('')

    const form = new FormData(event.currentTarget)

    try {
      const response = await fetch(`${apiUrl}/register`, {
        method: 'POST',
        credentials: 'include',
        headers: await sessionHeaders(),
        body: JSON.stringify({
          full_name: form.get('full_name'),
          preferred_name: form.get('preferred_name') || null,
          email: form.get('email'),
          password: form.get('password'),
          password_confirmation: form.get('password_confirmation'),
        }),
      })

      setMessage(
        response.ok
          ? 'Conta criada. Você ainda não possui vínculo paroquial nem cadastro de servo.'
          : 'Não foi possível criar a conta. Revise os dados informados.',
      )
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
        Entre ou crie sua conta. O vínculo com uma paróquia acontece separadamente.
      </p>

      <section className="login-card" aria-labelledby="login-title">
        <div className="mode-switch" aria-label="Escolha entre entrar e criar conta">
          <button type="button" className={mode === 'login' ? 'active' : ''} onClick={() => setMode('login')}>Entrar</button>
          <button type="button" className={mode === 'register' ? 'active' : ''} onClick={() => setMode('register')}>Criar conta</button>
        </div>

        <h2 id="login-title">{mode === 'login' ? 'Acessar' : 'Criar conta'}</h2>
        {mode === 'login' ? (
          <form onSubmit={login}>
            <label htmlFor="login-email">E-mail</label>
            <input id="login-email" name="email" type="email" autoComplete="email" required />

            <label htmlFor="login-password">Senha</label>
            <input id="login-password" name="password" type="password" autoComplete="current-password" required />

            <button type="submit" disabled={loading}>
              {loading ? 'Entrando…' : 'Entrar'}
            </button>
          </form>
        ) : (
          <form onSubmit={register}>
            <label htmlFor="full-name">Nome completo</label>
            <input id="full-name" name="full_name" type="text" autoComplete="name" required />

            <label htmlFor="preferred-name">Como prefere ser chamado</label>
            <input id="preferred-name" name="preferred_name" type="text" autoComplete="nickname" />

            <label htmlFor="register-email">E-mail</label>
            <input id="register-email" name="email" type="email" autoComplete="email" required />

            <label htmlFor="register-password">Senha</label>
            <input id="register-password" name="password" type="password" minLength={10} autoComplete="new-password" required />

            <label htmlFor="password-confirmation">Confirmar senha</label>
            <input id="password-confirmation" name="password_confirmation" type="password" minLength={10} autoComplete="new-password" required />

            <button type="submit" disabled={loading}>
              {loading ? 'Criando…' : 'Criar minha conta'}
            </button>
          </form>
        )}

        <div className="separator"><span>ou</span></div>

        <a className="google-button" href={`${apiUrl}/auth/google/redirect`}>
          Continuar com Google
        </a>

        <p className="invitation-note">Criar uma conta não torna você servo e não concede acesso a nenhuma paróquia.</p>
        {message && <p className="feedback" role="status">{message}</p>}
      </section>
    </main>
  )
}

export default App

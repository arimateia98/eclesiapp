import './App.css'
import { useCallback, useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'

type Parish = { id: string; name: string; timezone: string }
type Account = {
  id: string
  email: string
  active_parish_id: string | null
  person: { id: string; full_name: string; preferred_name: string | null }
  parishes: Parish[]
}
type Area = { id: string; code: string; name: string; functions_count: number }
type PastoralFunction = { id: string; name: string; area_name: string | null }
type Servant = {
  id: string
  status: string
  has_user: boolean
  person: { full_name: string; preferred_name: string | null; email: string | null; phone: string | null }
  functions: Array<{ id: string; status: string; function_name: string; area_name: string | null }>
}

function App() {
  const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8080'
  const query = useMemo(() => new URLSearchParams(window.location.search), [])
  const [message, setMessage] = useState(
    query.has('auth_error') ? 'Não foi possível entrar com esta conta Google.' : '',
  )
  const [loading, setLoading] = useState(false)
  const [checkingSession, setCheckingSession] = useState(true)
  const [mode, setMode] = useState<'login' | 'register'>('login')
  const [account, setAccount] = useState<Account | null>(null)
  const [activeParishId, setActiveParishId] = useState('')
  const [servants, setServants] = useState<Servant[]>([])
  const [areas, setAreas] = useState<Area[]>([])
  const [functions, setFunctions] = useState<PastoralFunction[]>([])
  const [workspaceError, setWorkspaceError] = useState('')

  const sessionHeaders = useCallback(async () => {
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
  }, [apiUrl])

  const loadAccount = useCallback(async () => {
    try {
      const response = await fetch(`${apiUrl}/api/v1/me`, {
        credentials: 'include',
        headers: { Accept: 'application/json' },
      })

      if (!response.ok) {
        setAccount(null)
        return
      }

      const payload = (await response.json()) as { data: Account }
      setAccount(payload.data)
      setActiveParishId(payload.data.active_parish_id ?? payload.data.parishes[0]?.id ?? '')
      if (query.get('auth') === 'google') setMessage('Acesso com Google confirmado.')
    } catch {
      setMessage('A API não está disponível. Tente novamente.')
    } finally {
      setCheckingSession(false)
    }
  }, [apiUrl, query])

  const loadWorkspace = useCallback(async (parishId: string) => {
    if (!parishId) return
    setWorkspaceError('')

    try {
      const headers = { Accept: 'application/json', 'X-Parish-Id': parishId }
      const [servantsResponse, areasResponse, functionsResponse] = await Promise.all([
        fetch(`${apiUrl}/api/v1/parishes/${parishId}/servants`, { credentials: 'include', headers }),
        fetch(`${apiUrl}/api/v1/parishes/${parishId}/pastoral-areas`, { credentials: 'include', headers }),
        fetch(`${apiUrl}/api/v1/parishes/${parishId}/pastoral-functions`, { credentials: 'include', headers }),
      ])

      if ([servantsResponse, areasResponse, functionsResponse].some((response) => response.status === 403)) {
        setWorkspaceError('Seu vínculo não possui permissão de padre ou administrador para gerenciar servos.')
        return
      }
      if (!servantsResponse.ok || !areasResponse.ok || !functionsResponse.ok) throw new Error()

      const servantsPayload = (await servantsResponse.json()) as { data: Servant[] }
      const areasPayload = (await areasResponse.json()) as { data: Area[] }
      const functionsPayload = (await functionsResponse.json()) as { data: PastoralFunction[] }
      setServants(servantsPayload.data)
      setAreas(areasPayload.data)
      setFunctions(functionsPayload.data)
    } catch {
      setWorkspaceError('Não foi possível carregar os dados pastorais agora.')
    }
  }, [apiUrl])

  // oxlint-disable-next-line react/set-state-in-effect -- sincroniza a sessÃ£o mantida pela API.
  useEffect(() => { void loadAccount() }, [loadAccount])
  // oxlint-disable-next-line react/set-state-in-effect -- sincroniza o contexto paroquial externo.
  useEffect(() => { void loadWorkspace(activeParishId) }, [activeParishId, loadWorkspace])

  async function submitSession(path: '/login' | '/register', body: Record<string, FormDataEntryValue | null>) {
    setLoading(true)
    setMessage('')

    try {
      const response = await fetch(`${apiUrl}${path}`, {
        method: 'POST', credentials: 'include', headers: await sessionHeaders(), body: JSON.stringify(body),
      })
      if (!response.ok) {
        setMessage(path === '/login' ? 'E-mail ou senha inválidos.' : 'Não foi possível criar a conta. Revise os dados.')
        return
      }
      setMessage(path === '/register' ? 'Conta criada. O vínculo paroquial e o cadastro de servo são separados.' : 'Acesso confirmado.')
      await loadAccount()
    } catch {
      setMessage('A API não está disponível. Tente novamente.')
    } finally {
      setLoading(false)
    }
  }

  function login(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    void submitSession('/login', { email: form.get('email'), password: form.get('password') })
  }

  function register(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    void submitSession('/register', {
      full_name: form.get('full_name'), preferred_name: form.get('preferred_name') || null,
      email: form.get('email'), password: form.get('password'),
      password_confirmation: form.get('password_confirmation'),
    })
  }

  async function createResource(event: FormEvent<HTMLFormElement>, path: string, body: Record<string, unknown>) {
    event.preventDefault()
    const formElement = event.currentTarget
    setLoading(true)
    setWorkspaceError('')
    try {
      const response = await fetch(`${apiUrl}/api/v1/parishes/${activeParishId}/${path}`, {
        method: 'POST', credentials: 'include',
        headers: { ...(await sessionHeaders()), 'X-Parish-Id': activeParishId },
        body: JSON.stringify(body),
      })
      if (!response.ok) throw new Error()
      formElement.reset()
      await loadWorkspace(activeParishId)
    } catch {
      setWorkspaceError('Não foi possível salvar. Confira os campos e tente novamente.')
    } finally {
      setLoading(false)
    }
  }

  async function logout() {
    await fetch(`${apiUrl}/logout`, { method: 'POST', credentials: 'include', headers: await sessionHeaders() })
    setAccount(null)
    setActiveParishId('')
  }

  if (checkingSession) return <main className="centered"><p>Carregando sua conta…</p></main>

  if (!account) {
    return (
      <main className="auth-shell">
        <p className="eyebrow">Gestão pastoral</p>
        <h1>eclEZapp</h1>
        <p className="lead">Entre ou crie sua conta. O vínculo com uma paróquia acontece separadamente.</p>
        <section className="login-card" aria-labelledby="login-title">
          <div className="mode-switch" aria-label="Escolha entre entrar e criar conta">
            <button type="button" className={mode === 'login' ? 'active' : ''} onClick={() => setMode('login')}>Entrar</button>
            <button type="button" className={mode === 'register' ? 'active' : ''} onClick={() => setMode('register')}>Criar conta</button>
          </div>
          <h2 id="login-title">{mode === 'login' ? 'Acessar' : 'Criar conta'}</h2>
          {mode === 'login' ? (
            <form onSubmit={login}>
              <label htmlFor="login-email">E-mail</label><input id="login-email" name="email" type="email" autoComplete="email" required />
              <label htmlFor="login-password">Senha</label><input id="login-password" name="password" type="password" autoComplete="current-password" required />
              <button type="submit" disabled={loading}>{loading ? 'Entrando…' : 'Entrar'}</button>
            </form>
          ) : (
            <form onSubmit={register}>
              <label htmlFor="full-name">Nome completo</label><input id="full-name" name="full_name" type="text" autoComplete="name" required />
              <label htmlFor="preferred-name">Como prefere ser chamado</label><input id="preferred-name" name="preferred_name" type="text" autoComplete="nickname" />
              <label htmlFor="register-email">E-mail</label><input id="register-email" name="email" type="email" autoComplete="email" required />
              <label htmlFor="register-password">Senha</label><input id="register-password" name="password" type="password" minLength={10} autoComplete="new-password" required />
              <label htmlFor="password-confirmation">Confirmar senha</label><input id="password-confirmation" name="password_confirmation" type="password" minLength={10} autoComplete="new-password" required />
              <button type="submit" disabled={loading}>{loading ? 'Criando…' : 'Criar minha conta'}</button>
            </form>
          )}
          <div className="separator"><span>ou</span></div>
          <a className="google-button" href={`${apiUrl}/auth/google/redirect`}>Continuar com Google</a>
          <p className="invitation-note">Criar uma conta não torna você servo e não concede acesso a nenhuma paróquia.</p>
          {message && <p className="feedback" role="status">{message}</p>}
        </section>
      </main>
    )
  }

  const activeParish = account.parishes.find((parish) => parish.id === activeParishId)

  return (
    <main className="dashboard-shell">
      <header className="topbar">
        <div><p className="eyebrow">eclEZapp</p><h2>Olá, {account.person.preferred_name ?? account.person.full_name}</h2></div>
        <button type="button" className="secondary" onClick={() => void logout()}>Sair</button>
      </header>

      {account.parishes.length === 0 ? (
        <section className="empty-state"><h3>Sua conta está pronta</h3><p>Você ainda não possui vínculo com uma paróquia e não é um servo cadastrado. Um padre ou administrador poderá criar esses vínculos separadamente.</p></section>
      ) : (
        <>
          <section className="parish-context">
            <div><span>Paróquia ativa</span><strong>{activeParish?.name}</strong></div>
            {account.parishes.length > 1 && <select value={activeParishId} onChange={(event) => setActiveParishId(event.target.value)} aria-label="Paróquia ativa">{account.parishes.map((parish) => <option key={parish.id} value={parish.id}>{parish.name}</option>)}</select>}
          </section>
          {workspaceError && <p className="alert" role="alert">{workspaceError}</p>}
          {!workspaceError && (
            <div className="workspace-grid">
              <section className="panel">
                <div className="panel-heading"><div><span>Cadastro operacional</span><h3>Servos</h3></div><strong>{servants.length}</strong></div>
                <form onSubmit={(event) => { const form = new FormData(event.currentTarget); void createResource(event, 'servants', { full_name: form.get('full_name'), preferred_name: form.get('preferred_name') || null, phone: form.get('phone') || null }) }}>
                  <label>Nome completo<input name="full_name" required /></label>
                  <label>Nome preferido<input name="preferred_name" /></label>
                  <label>Telefone<input name="phone" /></label>
                  <button disabled={loading}>Cadastrar servo</button>
                </form>
                <div className="records">{servants.length === 0 ? <p>Nenhum servo cadastrado.</p> : servants.map((servant) => (
                  <article className="record" key={servant.id}>
                    <div><strong>{servant.person.preferred_name ?? servant.person.full_name}</strong><small>{servant.has_user ? 'Possui conta' : 'Sem conta de usuário'}</small></div>
                    <div className="tags">{servant.functions.map((item) => <span key={item.id}>{item.area_name} · {item.function_name}</span>)}</div>
                    {functions.length > 0 && <form className="inline-form" onSubmit={(event) => { const form = new FormData(event.currentTarget); void createResource(event, `servants/${servant.id}/functions`, { pastoral_function_id: form.get('pastoral_function_id') }) }}><select name="pastoral_function_id" required defaultValue=""><option value="" disabled>Escolha uma função</option>{functions.map((item) => <option key={item.id} value={item.id}>{item.area_name} · {item.name}</option>)}</select><button disabled={loading}>Habilitar</button></form>}
                  </article>
                ))}</div>
              </section>

              <div className="catalog-column">
                <section className="panel compact">
                  <div className="panel-heading"><div><span>Catálogo</span><h3>Áreas pastorais</h3></div><strong>{areas.length}</strong></div>
                  <form onSubmit={(event) => { const form = new FormData(event.currentTarget); void createResource(event, 'pastoral-areas', { code: form.get('code'), name: form.get('name') }) }}>
                    <label>Nome<input name="name" placeholder="Liturgia" required /></label>
                    <label>Código<input name="code" placeholder="LITURGIA" pattern="[A-Za-z0-9_]+" required /></label>
                    <button disabled={loading}>Criar área</button>
                  </form>
                  <div className="tags">{areas.map((area) => <span key={area.id}>{area.name} · {area.functions_count}</span>)}</div>
                </section>

                <section className="panel compact">
                  <div className="panel-heading"><div><span>Catálogo</span><h3>Funções</h3></div><strong>{functions.length}</strong></div>
                  {areas.length === 0 ? <p>Crie uma área pastoral primeiro.</p> : <form onSubmit={(event) => { const form = new FormData(event.currentTarget); void createResource(event, 'pastoral-functions', { pastoral_area_id: form.get('pastoral_area_id'), code: form.get('code'), name: form.get('name'), assignment_mode: 'PERSON', requires_qualification: true }) }}>
                    <label>Área<select name="pastoral_area_id" required>{areas.map((area) => <option key={area.id} value={area.id}>{area.name}</option>)}</select></label>
                    <label>Nome<input name="name" placeholder="Leitor 1" required /></label>
                    <label>Código<input name="code" placeholder="LEITOR_1" pattern="[A-Za-z0-9_]+" required /></label>
                    <button disabled={loading}>Criar função</button>
                  </form>}
                </section>
              </div>
            </div>
          )}
        </>
      )}
    </main>
  )
}

export default App

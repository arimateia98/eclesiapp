<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import AuthPanel from './components/AuthPanel.vue'
import InvitationAcceptancePanel from './components/InvitationAcceptancePanel.vue'
import OrganizationDashboard from './components/OrganizationDashboard.vue'
import OrganizationWorkspace from './components/OrganizationWorkspace.vue'
import {
  addOrganizationMember,
  ApiError,
  createOrganization,
  fetchHealth,
  fetchOrganizationMembers,
  fetchOrganizations,
  inviteOrganizationMember,
  logout,
} from './services/api'
import { clearSession, persistSession, restoreSession } from './services/session'
import type {
  AddOrganizationMemberInput,
  AuthSession,
  CreateOrganizationInput,
  Organization,
  OrganizationMembership,
} from './types/api'

const session = ref<AuthSession | null>(restoreSession())
const organizations = ref<Organization[]>([])
const selectedOrganization = ref<Organization | null>(null)
const members = ref<OrganizationMembership[]>([])
const invitationToken = ref(new window.URLSearchParams(window.location.search).get('invitation'))
const apiOnline = ref(false)
const loadingOrganizations = ref(false)
const creatingOrganization = ref(false)
const loadingMembers = ref(false)
const addingMember = ref(false)
const invitingPersonId = ref<string | null>(null)
const dashboardError = ref<string | null>(null)
const dashboardNotice = ref<string | null>(null)
const workspaceError = ref<string | null>(null)
const workspaceNotice = ref<string | null>(null)
const controller = new AbortController()

onMounted(async () => {
  try {
    await fetchHealth(controller.signal)
    apiOnline.value = true
  } catch {
    apiOnline.value = false
  }

  if (session.value) {
    await loadOrganizations()
  }
})

onBeforeUnmount(() => controller.abort())

function messageFrom(error: unknown): string {
  return error instanceof ApiError
    ? error.message
    : 'Não foi possível concluir esta ação. Tente novamente.'
}

function handleExpiredSession(error: unknown): boolean {
  if (error instanceof ApiError && error.status === 401) {
    clearSession()
    session.value = null
    organizations.value = []
    selectedOrganization.value = null
    members.value = []

    return true
  }

  return false
}

async function loadOrganizations(): Promise<void> {
  if (!session.value) {
    return
  }

  loadingOrganizations.value = true
  dashboardError.value = null

  try {
    organizations.value = await fetchOrganizations(session.value.token)
  } catch (error) {
    if (!handleExpiredSession(error)) {
      dashboardError.value = messageFrom(error)
    }
  } finally {
    loadingOrganizations.value = false
  }
}

async function handleAuthenticated(authSession: AuthSession): Promise<void> {
  persistSession(authSession)
  session.value = authSession
  clearInvitationFromUrl()
  dashboardNotice.value = `Bem-vindo, ${authSession.user.name}. Seu acesso está pronto.`
  await loadOrganizations()
}

function clearInvitationFromUrl(): void {
  if (!invitationToken.value) {
    return
  }

  const url = new window.URL(window.location.href)
  url.searchParams.delete('invitation')
  window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`)
  invitationToken.value = null
}

function cancelInvitation(): void {
  clearInvitationFromUrl()
}

async function handleCreateOrganization(input: CreateOrganizationInput): Promise<void> {
  if (!session.value) {
    return
  }

  creatingOrganization.value = true
  dashboardError.value = null
  dashboardNotice.value = null

  try {
    const organization = await createOrganization(session.value.token, input)
    organizations.value = [...organizations.value, organization].sort((left, right) =>
      left.name.localeCompare(right.name, 'pt-BR'),
    )
    dashboardNotice.value = `${organization.name} foi criada com sucesso.`
  } catch (error) {
    if (!handleExpiredSession(error)) {
      dashboardError.value = messageFrom(error)
    }
  } finally {
    creatingOrganization.value = false
  }
}

async function handleSelectOrganization(organization: Organization): Promise<void> {
  selectedOrganization.value = organization
  members.value = []
  workspaceNotice.value = null
  await loadMembers()
}

function handleCloseWorkspace(): void {
  selectedOrganization.value = null
  members.value = []
  workspaceError.value = null
  workspaceNotice.value = null
}

async function loadMembers(): Promise<void> {
  if (!session.value || !selectedOrganization.value) {
    return
  }

  loadingMembers.value = true
  workspaceError.value = null

  try {
    members.value = await fetchOrganizationMembers(
      session.value.token,
      selectedOrganization.value.id,
    )
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    loadingMembers.value = false
  }
}

async function handleAddMember(input: AddOrganizationMemberInput): Promise<void> {
  if (!session.value || !selectedOrganization.value) {
    return
  }

  addingMember.value = true
  workspaceError.value = null
  workspaceNotice.value = null

  try {
    const membership = await addOrganizationMember(
      session.value.token,
      selectedOrganization.value.id,
      input,
    )
    members.value = [...members.value, membership].sort((left, right) =>
      left.person.full_name.localeCompare(right.person.full_name, 'pt-BR'),
    )
    workspaceNotice.value = `${membership.person.full_name} foi cadastrado(a) sem criar uma conta.`
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    addingMember.value = false
  }
}

async function handleInviteMember(personId: string): Promise<void> {
  if (!session.value || !selectedOrganization.value) {
    return
  }

  invitingPersonId.value = personId
  workspaceError.value = null
  workspaceNotice.value = null

  try {
    await inviteOrganizationMember(
      session.value.token,
      selectedOrganization.value.id,
      personId,
    )
    const person = members.value.find((membership) => membership.person.id === personId)?.person
    workspaceNotice.value = person
      ? `Convite enviado para ${person.email}. No ambiente local, abra o Mailpit para acessá-lo.`
      : 'Convite enviado. No ambiente local, abra o Mailpit para acessá-lo.'
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    invitingPersonId.value = null
  }
}

async function handleLogout(): Promise<void> {
  const activeSession = session.value

  clearSession()
  session.value = null
  organizations.value = []
  selectedOrganization.value = null
  members.value = []
  dashboardNotice.value = null
  dashboardError.value = null
  workspaceNotice.value = null
  workspaceError.value = null

  if (activeSession) {
    try {
      await logout(activeSession.token)
    } catch {
      // Local access is already removed; server-side expiration remains the fallback.
    }
  }
}
</script>

<template>
  <main
    v-if="!session"
    class="auth-shell"
  >
    <a
      class="brand brand--floating"
      href="#"
      aria-label="Eclesiapp — início"
    >
      <span
        class="brand__mark"
        aria-hidden="true"
      >E</span>
      <span>Eclesiapp</span>
    </a>
    <InvitationAcceptancePanel
      v-if="invitationToken"
      :token="invitationToken"
      @authenticated="handleAuthenticated"
      @cancel="cancelInvitation"
    />
    <AuthPanel
      v-else
      @authenticated="handleAuthenticated"
    />
    <footer class="auth-footer">
      <span
        class="api-indicator"
        :class="{ 'is-online': apiOnline }"
        data-test="api-status"
      >
        <span aria-hidden="true" />
        {{ apiOnline ? 'API conectada' : 'API indisponível' }}
      </span>
      <span>Feito para quem cuida de comunidades.</span>
    </footer>
  </main>

  <OrganizationWorkspace
    v-else-if="selectedOrganization"
    :session="session"
    :organization="selectedOrganization"
    :members="members"
    :loading="loadingMembers"
    :adding="addingMember"
    :inviting-person-id="invitingPersonId"
    :error="workspaceError"
    :notice="workspaceNotice"
    @back="handleCloseWorkspace"
    @logout="handleLogout"
    @refresh="loadMembers"
    @add-member="handleAddMember"
    @invite="handleInviteMember"
  />

  <OrganizationDashboard
    v-else
    :session="session"
    :organizations="organizations"
    :loading="loadingOrganizations"
    :creating="creatingOrganization"
    :error="dashboardError"
    :notice="dashboardNotice"
    :api-online="apiOnline"
    @logout="handleLogout"
    @refresh="loadOrganizations"
    @create="handleCreateOrganization"
    @select="handleSelectOrganization"
  />
</template>

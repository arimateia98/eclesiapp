<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import AuthPanel from './components/AuthPanel.vue'
import InvitationAcceptancePanel from './components/InvitationAcceptancePanel.vue'
import OrganizationDashboard from './components/OrganizationDashboard.vue'
import OrganizationWorkspace from './components/OrganizationWorkspace.vue'
import {
  addOrganizationMember,
  ApiError,
  assignPersonFunction,
  createAssignment,
  createMinistryType,
  createOrganization,
  createServiceFunction,
  createEvent,
  createEventType,
  createInternalMission,
  createLocation,
  fetchEvents,
  fetchAssignments,
  fetchEligibleMembers,
  fetchEventTypes,
  fetchHealth,
  fetchInternalMissions,
  fetchLocations,
  fetchMinistryTypes,
  fetchOrganizationMembers,
  fetchOrganizations,
  fetchPersonFunctions,
  fetchServiceFunctions,
  inviteOrganizationMember,
  logout,
  removePersonFunction,
} from './services/api'
import { clearSession, persistSession, restoreSession } from './services/session'
import type {
  AddOrganizationMemberInput,
  Assignment,
  AuthSession,
  CreateMinistryTypeInput,
  CreateEventInput,
  CreateEventTypeInput,
  CreateInternalMissionInput,
  CreateLocationInput,
  CreateOrganizationInput,
  CreateServiceFunctionInput,
  MinistryType,
  EventType,
  Location,
  Mission,
  Organization,
  OrganizationMembership,
  PersonFunction,
  ServiceFunction,
  ScheduledEvent,
} from './types/api'

const session = ref<AuthSession | null>(restoreSession())
const organizations = ref<Organization[]>([])
const selectedOrganization = ref<Organization | null>(null)
const members = ref<OrganizationMembership[]>([])
const ministryTypes = ref<MinistryType[]>([])
const serviceFunctions = ref<ServiceFunction[]>([])
const personFunctions = ref<PersonFunction[]>([])
const selectedPersonId = ref<string | null>(null)
const eventTypes = ref<EventType[]>([])
const locations = ref<Location[]>([])
const events = ref<ScheduledEvent[]>([])
const missions = ref<Mission[]>([])
const selectedEventId = ref<string | null>(null)
const selectedMissionId = ref<string | null>(null)
const selectedSlotId = ref<string | null>(null)
const assignments = ref<Assignment[]>([])
const eligibleMembers = ref<OrganizationMembership[]>([])
const invitationToken = ref(new window.URLSearchParams(window.location.search).get('invitation'))
const apiOnline = ref(false)
const loadingOrganizations = ref(false)
const creatingOrganization = ref(false)
const loadingMembers = ref(false)
const loadingCatalog = ref(false)
const loadingCapabilities = ref(false)
const addingMember = ref(false)
const catalogBusy = ref<'type' | 'function' | null>(null)
const updatingFunctionId = ref<string | null>(null)
const loadingSchedule = ref(false)
const loadingMissions = ref(false)
const loadingAssignments = ref(false)
const loadingEligibleMembers = ref(false)
const creatingAssignment = ref(false)
const scheduleBusy = ref<'event-type' | 'location' | 'event' | 'mission' | null>(null)
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
    ministryTypes.value = []
    serviceFunctions.value = []
    personFunctions.value = []
    selectedPersonId.value = null
    resetSchedule()

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
  ministryTypes.value = []
  serviceFunctions.value = []
  personFunctions.value = []
  selectedPersonId.value = null
  workspaceNotice.value = null
  await Promise.all([loadMembers(), loadCatalog(), loadSchedule()])
}

function handleCloseWorkspace(): void {
  selectedOrganization.value = null
  members.value = []
  ministryTypes.value = []
  serviceFunctions.value = []
  personFunctions.value = []
  selectedPersonId.value = null
  resetSchedule()
  workspaceError.value = null
  workspaceNotice.value = null
}

function resetSchedule(): void {
  eventTypes.value = []
  locations.value = []
  events.value = []
  missions.value = []
  selectedEventId.value = null
  resetAssignments()
  loadingSchedule.value = false
  loadingMissions.value = false
  scheduleBusy.value = null
}

function resetAssignments(): void {
  selectedMissionId.value = null
  selectedSlotId.value = null
  assignments.value = []
  eligibleMembers.value = []
  loadingAssignments.value = false
  loadingEligibleMembers.value = false
  creatingAssignment.value = false
}

async function loadSchedule(): Promise<void> {
  if (!session.value || !selectedOrganization.value) {
    return
  }

  loadingSchedule.value = true
  workspaceError.value = null

  try {
    const [loadedEventTypes, loadedLocations, loadedEvents] = await Promise.all([
      fetchEventTypes(session.value.token, selectedOrganization.value.id),
      fetchLocations(session.value.token, selectedOrganization.value.id),
      fetchEvents(session.value.token, selectedOrganization.value.id),
    ])
    eventTypes.value = loadedEventTypes
    locations.value = loadedLocations
    events.value = loadedEvents

    if (selectedEventId.value && !loadedEvents.some((event) => event.id === selectedEventId.value)) {
      selectedEventId.value = null
      missions.value = []
      resetAssignments()
    }
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    loadingSchedule.value = false
  }
}

async function handleSelectEvent(eventId: string): Promise<void> {
  if (!session.value || !selectedOrganization.value) {
    return
  }

  selectedEventId.value = eventId
  missions.value = []
  resetAssignments()
  loadingMissions.value = true
  workspaceError.value = null

  try {
    missions.value = await fetchInternalMissions(
      session.value.token,
      selectedOrganization.value.id,
      eventId,
    )
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    loadingMissions.value = false
  }
}

async function handleSelectMission(missionId: string): Promise<void> {
  if (!session.value || !selectedOrganization.value || !selectedEventId.value) {
    return
  }

  if (selectedMissionId.value === missionId) {
    resetAssignments()
    return
  }

  selectedMissionId.value = missionId
  selectedSlotId.value = null
  eligibleMembers.value = []
  assignments.value = []
  loadingAssignments.value = true
  workspaceError.value = null

  try {
    assignments.value = await fetchAssignments(
      session.value.token,
      selectedOrganization.value.id,
      selectedEventId.value,
      missionId,
    )
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    loadingAssignments.value = false
  }
}

async function handleSelectAssignmentSlot(missionId: string, slotId: string): Promise<void> {
  if (!session.value || !selectedOrganization.value || !selectedEventId.value) {
    return
  }

  selectedMissionId.value = missionId
  selectedSlotId.value = slotId
  eligibleMembers.value = []
  loadingEligibleMembers.value = true
  workspaceError.value = null

  try {
    eligibleMembers.value = await fetchEligibleMembers(
      session.value.token,
      selectedOrganization.value.id,
      selectedEventId.value,
      missionId,
      slotId,
    )
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    loadingEligibleMembers.value = false
  }
}

async function handleCreateAssignment(missionId: string, slotId: string, personId: string): Promise<void> {
  if (!session.value || !selectedOrganization.value || !selectedEventId.value) {
    return
  }

  creatingAssignment.value = true
  workspaceError.value = null
  workspaceNotice.value = null

  try {
    const assignment = await createAssignment(
      session.value.token,
      selectedOrganization.value.id,
      selectedEventId.value,
      missionId,
      { mission_slot_id: slotId, person_id: personId },
    )
    assignments.value = [...assignments.value, assignment]
    eligibleMembers.value = eligibleMembers.value.filter(
      (membership) => membership.person.id !== personId,
    )
    workspaceNotice.value = `${assignment.person.preferred_name || assignment.person.full_name} foi designado(a).`
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    creatingAssignment.value = false
  }
}

async function handleCreateEventType(input: CreateEventTypeInput): Promise<void> {
  if (!session.value || !selectedOrganization.value) {
    return
  }

  scheduleBusy.value = 'event-type'
  workspaceError.value = null
  workspaceNotice.value = null

  try {
    const eventType = await createEventType(session.value.token, selectedOrganization.value.id, input)
    eventTypes.value = [...eventTypes.value, eventType].sort((left, right) =>
      left.name.localeCompare(right.name, 'pt-BR'),
    )
    workspaceNotice.value = `${eventType.name} foi adicionado aos tipos de evento.`
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    scheduleBusy.value = null
  }
}

async function handleCreateLocation(input: CreateLocationInput): Promise<void> {
  if (!session.value || !selectedOrganization.value) {
    return
  }

  scheduleBusy.value = 'location'
  workspaceError.value = null
  workspaceNotice.value = null

  try {
    const location = await createLocation(session.value.token, selectedOrganization.value.id, input)
    locations.value = [...locations.value, location].sort((left, right) =>
      left.name.localeCompare(right.name, 'pt-BR'),
    )
    workspaceNotice.value = `${location.name} foi adicionado aos locais.`
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    scheduleBusy.value = null
  }
}

async function handleCreateEvent(input: CreateEventInput): Promise<void> {
  if (!session.value || !selectedOrganization.value) {
    return
  }

  scheduleBusy.value = 'event'
  workspaceError.value = null
  workspaceNotice.value = null

  try {
    const event = await createEvent(session.value.token, selectedOrganization.value.id, input)
    events.value = [...events.value, event].sort((left, right) =>
      left.starts_at.localeCompare(right.starts_at),
    )
    selectedEventId.value = event.id
    missions.value = []
    resetAssignments()
    workspaceNotice.value = `${event.title} foi criado como rascunho privado.`
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    scheduleBusy.value = null
  }
}

async function handleCreateMission(
  eventId: string,
  input: CreateInternalMissionInput,
): Promise<void> {
  if (!session.value || !selectedOrganization.value || selectedEventId.value !== eventId) {
    return
  }

  scheduleBusy.value = 'mission'
  workspaceError.value = null
  workspaceNotice.value = null

  try {
    const mission = await createInternalMission(
      session.value.token,
      selectedOrganization.value.id,
      eventId,
      input,
    )
    missions.value = [...missions.value, mission]
    workspaceNotice.value = `${mission.title} foi criada com ${mission.slots.length} vaga(s).`
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    scheduleBusy.value = null
  }
}

async function loadCatalog(): Promise<void> {
  if (!session.value || !selectedOrganization.value) {
    return
  }

  loadingCatalog.value = true
  workspaceError.value = null

  try {
    const [types, functions] = await Promise.all([
      fetchMinistryTypes(session.value.token, selectedOrganization.value.id),
      fetchServiceFunctions(session.value.token, selectedOrganization.value.id),
    ])
    ministryTypes.value = types
    serviceFunctions.value = functions
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    loadingCatalog.value = false
  }
}

async function handleCreateMinistryType(input: CreateMinistryTypeInput): Promise<void> {
  if (!session.value || !selectedOrganization.value) {
    return
  }

  catalogBusy.value = 'type'
  workspaceError.value = null
  workspaceNotice.value = null

  try {
    const ministryType = await createMinistryType(
      session.value.token,
      selectedOrganization.value.id,
      input,
    )
    ministryTypes.value = [...ministryTypes.value, ministryType].sort((left, right) =>
      left.name.localeCompare(right.name, 'pt-BR'),
    )
    workspaceNotice.value = `${ministryType.name} foi adicionado ao catálogo de ministérios.`
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    catalogBusy.value = null
  }
}

async function handleCreateServiceFunction(input: CreateServiceFunctionInput): Promise<void> {
  if (!session.value || !selectedOrganization.value) {
    return
  }

  catalogBusy.value = 'function'
  workspaceError.value = null
  workspaceNotice.value = null

  try {
    const serviceFunction = await createServiceFunction(
      session.value.token,
      selectedOrganization.value.id,
      input,
    )
    serviceFunctions.value = [...serviceFunctions.value, serviceFunction].sort((left, right) =>
      left.name.localeCompare(right.name, 'pt-BR'),
    )
    workspaceNotice.value = `${serviceFunction.name} foi adicionada às funções de serviço.`
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    catalogBusy.value = null
  }
}

async function handleSelectPerson(personId: string): Promise<void> {
  if (!session.value || !selectedOrganization.value) {
    return
  }

  selectedPersonId.value = personId
  personFunctions.value = []
  loadingCapabilities.value = true
  workspaceError.value = null

  try {
    personFunctions.value = await fetchPersonFunctions(
      session.value.token,
      selectedOrganization.value.id,
      personId,
    )
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    loadingCapabilities.value = false
  }
}

async function handleToggleFunction(serviceFunctionId: string, assigned: boolean): Promise<void> {
  if (!session.value || !selectedOrganization.value || !selectedPersonId.value) {
    return
  }

  updatingFunctionId.value = serviceFunctionId
  workspaceError.value = null
  workspaceNotice.value = null

  try {
    if (assigned) {
      await removePersonFunction(
        session.value.token,
        selectedOrganization.value.id,
        selectedPersonId.value,
        serviceFunctionId,
      )
      personFunctions.value = personFunctions.value.filter(
        (assignment) => assignment.service_function_id !== serviceFunctionId,
      )
      workspaceNotice.value = 'A função foi removida da pessoa.'
    } else {
      const assignment = await assignPersonFunction(
        session.value.token,
        selectedOrganization.value.id,
        selectedPersonId.value,
        serviceFunctionId,
      )
      personFunctions.value = [...personFunctions.value, assignment]
      workspaceNotice.value = 'A função foi atribuída à pessoa.'
    }
  } catch (error) {
    if (!handleExpiredSession(error)) {
      workspaceError.value = messageFrom(error)
    }
  } finally {
    updatingFunctionId.value = null
  }
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
  ministryTypes.value = []
  serviceFunctions.value = []
  personFunctions.value = []
  selectedPersonId.value = null
  resetSchedule()
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
    :ministry-types="ministryTypes"
    :service-functions="serviceFunctions"
    :person-functions="personFunctions"
    :selected-person-id="selectedPersonId"
    :event-types="eventTypes"
    :locations="locations"
    :events="events"
    :missions="missions"
    :selected-event-id="selectedEventId"
    :selected-mission-id="selectedMissionId"
    :selected-slot-id="selectedSlotId"
    :assignments="assignments"
    :eligible-members="eligibleMembers"
    :loading="loadingMembers"
    :loading-catalog="loadingCatalog || loadingCapabilities"
    :catalog-busy="catalogBusy"
    :updating-function-id="updatingFunctionId"
    :loading-schedule="loadingSchedule"
    :loading-missions="loadingMissions"
    :loading-assignments="loadingAssignments"
    :loading-eligible-members="loadingEligibleMembers"
    :creating-assignment="creatingAssignment"
    :schedule-busy="scheduleBusy"
    :adding="addingMember"
    :inviting-person-id="invitingPersonId"
    :error="workspaceError"
    :notice="workspaceNotice"
    @back="handleCloseWorkspace"
    @logout="handleLogout"
    @refresh="loadMembers"
    @add-member="handleAddMember"
    @invite="handleInviteMember"
    @refresh-catalog="loadCatalog"
    @create-ministry-type="handleCreateMinistryType"
    @create-service-function="handleCreateServiceFunction"
    @select-person="handleSelectPerson"
    @toggle-function="handleToggleFunction"
    @refresh-schedule="loadSchedule"
    @select-event="handleSelectEvent"
    @create-event-type="handleCreateEventType"
    @create-location="handleCreateLocation"
    @create-event="handleCreateEvent"
    @create-mission="handleCreateMission"
    @select-mission="handleSelectMission"
    @select-assignment-slot="handleSelectAssignmentSlot"
    @create-assignment="handleCreateAssignment"
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

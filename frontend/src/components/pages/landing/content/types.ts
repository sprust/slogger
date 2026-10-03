export interface McpScenarioStep {
    tool: string,
    params: string,
    result: string,
}

export interface McpScenario {
    name: string,
    title: string,
    question: string,
    steps: Array<McpScenarioStep>,
    answer: string,
}

export interface LandingTopBarText {
    title: string,
    subtitle: string,
    login: string,
    github: string,
}

export interface LandingFeature {
    title: string,
    text: string,
}

export interface LandingAboutText {
    title: string,
    paragraphs: Array<string>,
    features: Array<LandingFeature>,
}

export interface LandingDataPathNodes {
    client: string,
    receiver: string,
    buffer: string,
    transporter: string,
    pending: string,
    clickhouse: string,
    backend: string,
    panel: string,
}

export interface LandingDataPathEdges {
    clientReceiver: string,
    receiverBuffer: string,
    transporterBuffer: string,
    transporterPending: string,
    transporterClickhouse: string,
    backendClickhouse: string,
    panelBackend: string,
}

export interface LandingDataPathText {
    title: string,
    paragraphs: Array<string>,
    nodes: LandingDataPathNodes,
    edges: LandingDataPathEdges,
}

export interface LandingTraceTreeText {
    title: string,
    paragraphs: Array<string>,
    demoCaption: string,
}

export interface LandingFilterExampleText {
    name: string,
    label: string,
    text: string,
}

export interface LandingTraceListText {
    paragraphs: Array<string>,
    examplesCaption: string,
    examples: Array<LandingFilterExampleText>,
    resetExample: string,
    found: string,
    of: string,
    empty: string,
}

export interface LandingSearchText {
    title: string,
    paragraphs: Array<string>,
    traceList: LandingTraceListText,
}

export interface LandingGraphsText {
    title: string,
    paragraphs: Array<string>,
    filtersCaption: string,
    filters: Array<string>,
    demoCaption: string,
}

export interface LandingMetricsText {
    title: string,
    paragraphs: Array<string>,
    demoCaption: string,
}

export interface LandingChannel {
    name: string,
    text: string,
}

export interface LandingWatchersText {
    title: string,
    demoWatcherName: string,
    demoChannelName: string,
    paragraphs: Array<string>,
    ruleCaption: string,
    eventsCaption: string,
    channelsCaption: string,
    channels: Array<LandingChannel>,
    channelsNote: string,
}

export interface LandingMcpText {
    title: string,
    scenarios: Array<McpScenario>,
    paragraphs: Array<string>,
    scenariosCaption: string,
    answerCaption: string,
    connectCaption: string,
    connectCommand: string,
    connectNote: string,
}

export interface LandingStorage {
    name: string,
    role: string,
}

export interface LandingStackText {
    title: string,
    runtimeCaption: string,
    runtime: Array<string>,
    storageCaption: string,
    storageNameColumn: string,
    storageRoleColumn: string,
    storages: Array<LandingStorage>,
    cleanupCaption: string,
    cleanup: Array<string>,
}

export interface LandingLoginDialogText {
    title: string,
    email: string,
    password: string,
    submit: string,
    invalid: string,
}

export interface LandingInstallStep {
    title: string,
    text: string,
    code: string,
}

export interface LandingInstallText {
    title: string,
    paragraphs: Array<string>,
    steps: Array<LandingInstallStep>,
}

export interface LandingProtocolText {
    title: string,
    paragraphs: Array<string>,
    authCaption: string,
    authCode: string,
    messageCaption: string,
    messageCode: string,
    createCaption: string,
    createCode: string,
    updateCaption: string,
    updateCode: string,
    notes: Array<string>,
}

export interface LandingText {
    topBar: LandingTopBarText,
    loginDialog: LandingLoginDialogText,
    about: LandingAboutText,
    dataPath: LandingDataPathText,
    traceTree: LandingTraceTreeText,
    search: LandingSearchText,
    graphs: LandingGraphsText,
    metrics: LandingMetricsText,
    watchers: LandingWatchersText,
    mcp: LandingMcpText,
    stack: LandingStackText,
    install: LandingInstallText,
    protocol: LandingProtocolText,
}

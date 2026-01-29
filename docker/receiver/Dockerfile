FROM golang:1.25

ARG BUILD_TIME=1

RUN mkdir /app

WORKDIR /app

COPY servers/receiver /app
RUN rm -rf /app/bin
RUN rm -rf /app/storage

RUN chmod 777 -R /app

#RUN CGO_ENABLED=0 GOOS=linux go build -v -a -o /app/bin/receiver /app/cmd/server/main.go && chmod +x /app/bin/receiver
RUN make build
RUN make stats-build

# Container user & group
ARG USER_ID
ARG GROUP_ID

# Check if user and group doesn't exist before creating
RUN getent group "$GROUP_ID" || addgroup --gid "$GROUP_ID" user
RUN getent passwd "$USER_ID" || adduser --disabled-password --gecos '' --uid "$USER_ID" --gid "$GROUP_ID" user

USER "$USER_ID"

CMD ["/app/bin/receiver"]

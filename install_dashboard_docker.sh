#!/bin/bash

echo "=========================================="
echo "SCEH Dashboard - Docker Installation"
echo "=========================================="
echo ""

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    echo "ERROR: docker-compose not found"
    exit 1
fi

# Check if Moodle is running
echo "Checking Moodle container status..."
CONTAINER_NAME=$(docker-compose ps -q moodle 2>/dev/null)

if [ -z "$CONTAINER_NAME" ]; then
    echo "Moodle container not running. Starting..."
    docker-compose up -d
    echo "Waiting for Moodle to start (30 seconds)..."
    sleep 30
else
    echo "✓ Moodle container is running"
fi

# Get actual container name
CONTAINER_NAME=$(docker-compose ps | grep moodle | awk '{print $1}' | head -1)
if [ -z "$CONTAINER_NAME" ]; then
    # Try alternative method
    CONTAINER_NAME=$(docker ps --filter "name=moodle" --format "{{.Names}}" | head -1)
fi

echo "Container name: $CONTAINER_NAME"
echo ""

# Copy block to container
echo "Copying dashboard block to container..."
docker cp block_sceh_dashboard ${CONTAINER_NAME}:/bitnami/moodle/blocks/sceh_dashboard

if [ $? -eq 0 ]; then
    echo "✓ Files copied successfully"
else
    echo "✗ Failed to copy files"
    exit 1
fi

# Set permissions
echo "Setting permissions..."
docker exec ${CONTAINER_NAME} chown -R daemon:daemon /bitnami/moodle/blocks/sceh_dashboard
docker exec ${CONTAINER_NAME} chmod -R 755 /bitnami/moodle/blocks/sceh_dashboard

echo "✓ Permissions set"
echo ""

echo "=========================================="
echo "Installation Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Open your browser to: http://localhost:8080"
echo "2. Login as admin"
echo "3. Go to: Site Administration → Notifications"
echo "4. Click 'Upgrade Moodle database now'"
echo "5. Add the block to your homepage:"
echo "   - Turn editing on"
echo "   - Click 'Add a block'"
echo "   - Select 'Fellowship Training Dashboard'"
echo ""
echo "Opening browser..."
sleep 2

# Try to open browser
if command -v open &> /dev/null; then
    open http://localhost:8080
elif command -v xdg-open &> /dev/null; then
    xdg-open http://localhost:8080
else
    echo "Please manually open: http://localhost:8080"
fi
